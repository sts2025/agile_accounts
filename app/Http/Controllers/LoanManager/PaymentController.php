<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Loan;
use App\Models\MfiAccount;
use App\Models\MfiTransaction;
use App\Services\JournalPoster;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $manager = Auth::user()->loanManager;
        $isMfi = (bool) $manager->is_mfi;

        $payments = Payment::whereHas('loan', function($q) use ($manager) {
            $q->where('loan_manager_id', $manager->id);
        })->with('loan.client')->latest('payment_date')->paginate(15);

        // Fetch active loans for the modal dropdown
        $loans = $manager->loans()->where('status', 'active')->with('client', 'payments')->get();

        // For MFI tenants, attach each loan's client's available (unlocked)
        // savings balance so the "Pay from Savings" option in the modal can
        // show a live hint before the manager submits.
        if ($isMfi) {
            $savingsAccounts = MfiAccount::where('loan_manager_id', $manager->id)
                ->where('account_type', 'savings')
                ->where('status', 'active')
                ->get()
                ->keyBy('client_id');

            $loans->each(function ($loan) use ($savingsAccounts) {
                $account = $savingsAccounts->get($loan->client_id);
                $loan->savings_available = $account ? ($account->balance - $account->lien_amount) : null;
            });
        }

        return view('loan-manager.payments.index', compact('payments', 'loans', 'isMfi'));
    }

    public function store(Request $request)
    {
        $manager = Auth::user()->loanManager;
        $managerId = $manager->id;
        $isMfi = (bool) $manager->is_mfi;

        $validated = $request->validate([
            'loan_id'          => 'required|exists:loans,id',
            'principal_paid'   => 'required|numeric|min:0',
            'interest_paid'    => 'required|numeric|min:0',
            'payment_date'     => 'required|date',
            'payment_method'   => 'required|string',
            'reference_id'     => 'nullable|string|max:255',
            'notes'            => 'nullable|string|max:1000',
            'pay_from_savings' => 'nullable|boolean',
        ]);

        $validated['pay_from_savings'] = $request->boolean('pay_from_savings');

        try {
            $result = PaymentService::record($managerId, $isMfi, $validated);

            $message = 'Payment recorded successfully!';
            if ($result['compulsory_split'] > 0) {
                $message .= ' ' . number_format($result['compulsory_split']) . ' was also added to the client\'s savings as a compulsory top-up.';
            }

            return redirect()->route('payments.receipt', $result['payment']->id)
                             ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Confirm this payment's loan belongs to the currently authenticated
     * loan manager. Payment isn't tenant-scoped by itself (only its loan
     * is), so every action that accepts a Payment via route-model-binding
     * must check this before reading or touching it.
     */
    private function authorizePaymentAccess(Payment $payment): void
    {
        $managerId = Auth::user()->loanManager->id;
        abort_unless(
            $payment->loan && $payment->loan->loan_manager_id === $managerId,
            403
        );
    }

    public function showReceipt(Payment $payment)
    {
        $this->authorizePaymentAccess($payment);

        return view('loan-manager.payments.receipt-thermal', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $this->authorizePaymentAccess($payment);

        return view('loan-manager.payments.edit', compact('payment'));
    }

    /**
     * Correct a payment's basic details. The edit form only collects a
     * single total amount, so if it changed we rescale the existing
     * principal/interest split proportionally rather than losing the
     * breakdown. This does NOT reverse or replay any savings-wallet debit
     * or compulsory-savings top-up recorded at the time of the original
     * payment — those live in the separate mfi_transactions ledger and
     * are not linked back to a specific Payment row, so they're outside
     * what a safe automatic edit can adjust.
     */
    public function update(Request $request, Payment $payment)
    {
        $this->authorizePaymentAccess($payment);

        $validated = $request->validate([
            'amount_paid'    => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
            'reference_id'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $oldAmount = (float) $payment->amount_paid;
        $newAmount = (float) $validated['amount_paid'];

        if ($oldAmount > 0 && $oldAmount != $newAmount) {
            // Keep the existing principal/interest ratio when rescaling.
            $principalRatio = ((float) $payment->principal_paid) / $oldAmount;
            $principalPaid = round($newAmount * $principalRatio, 2);
            $interestPaid = round($newAmount - $principalPaid, 2);
        } else {
            $principalPaid = $payment->principal_paid;
            $interestPaid = $payment->interest_paid;
        }

        $payment->update([
            'amount_paid'    => $newAmount,
            'principal_paid' => $principalPaid,
            'interest_paid'  => $interestPaid,
            'payment_date'   => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'reference_id'   => $validated['reference_id'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        $message = 'Payment updated successfully.';
        if ($payment->wasChanged('amount_paid') && $payment->loan && $payment->loan->mfi_loan_product_id) {
            $message .= ' Note: any savings-wallet debit or compulsory-savings top-up tied to the original amount was not automatically adjusted — review the client\'s savings account if needed.';
        }

        return redirect()->route('loans.show', $payment->loan_id)->with('success', $message);
    }

    /**
     * Delete a payment record. Best-effort reversal: if it was funded from
     * the client's savings wallet, credit the amount back; if it had
     * pushed the loan to "paid", re-open the loan now that this payment
     * is gone. Compulsory-savings top-ups are flagged for manual review
     * rather than auto-reversed (see note on update()).
     */
    public function destroy(Payment $payment)
    {
        $this->authorizePaymentAccess($payment);

        $managerId = Auth::user()->loanManager->id;
        $warning = null;

        DB::transaction(function () use ($payment, $managerId, &$warning) {
            $loan = Loan::lockForUpdate()->find($payment->loan_id);

            if ($payment->payment_method === 'Savings Wallet' && $loan) {
                $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('client_id', $loan->client_id)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($savingsAccount) {
                    $savingsAccount->increment('balance', $payment->amount_paid);

                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $loan->client_id,
                        'mfi_account_id' => $savingsAccount->id,
                        'transaction_type' => 'deposit',
                        'amount' => $payment->amount_paid,
                        'credit' => $payment->amount_paid,
                        'debit' => 0,
                        'transaction_date' => now(),
                        'payment_method' => 'Savings Wallet',
                        'narration' => 'Reversal: deleted loan repayment (was Receipt: ' . ($payment->receipt_number ?? $payment->id) . ')',
                    ]);
                } else {
                    $warning = 'The client\'s savings wallet debit for this payment could not be reversed automatically (no active savings account found).';
                }
            } elseif ($loan && $loan->mfi_loan_product_id) {
                $warning = 'If this payment triggered a compulsory-savings top-up, it was not automatically reversed — review the client\'s savings account if needed.';
            }

            if ($payment->journal_entry_id) {
                JournalPoster::reverse($payment->journalEntry, 'Reversal (payment deleted)');
            }

            $payment->delete();

            if ($loan && $loan->status === 'paid') {
                $totalDue = $loan->principalInterestDue();
                $remainingPaid = $loan->payments()->sum('amount_paid');

                if ($remainingPaid < $totalDue) {
                    $loan->status = 'active';
                    $loan->save();
                }
            }
        });

        $message = 'Payment deleted successfully.';
        if ($warning) {
            $message .= ' ' . $warning;
        }

        return back()->with('success', $message);
    }
}