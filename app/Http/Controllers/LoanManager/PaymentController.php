<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Loan;
use App\Models\MfiAccount;
use App\Models\MfiProduct;
use App\Models\MfiTransaction;
use App\Services\JournalPoster;
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

        // Auto-calculate total amount based on the split
        $totalAmount = $validated['principal_paid'] + $validated['interest_paid'];

        if ($totalAmount <= 0) {
            return back()->with('error', 'Payment amount must be greater than zero.');
        }

        // Only MFI tenants can pay from savings; ignore the flag otherwise.
        $payFromSavings = $isMfi && $request->boolean('pay_from_savings');

        try {
            $result = DB::transaction(function () use ($validated, $totalAmount, $managerId, $isMfi, $payFromSavings) {

                $receiptNumber = !empty($validated['reference_id'])
                                    ? $validated['reference_id']
                                    : 'RCP-' . time() . rand(10, 99);

                $loan = Loan::with('client')->lockForUpdate()->find($validated['loan_id']);

                if (!$loan || $loan->loan_manager_id !== $managerId) {
                    throw new \Exception('Loan not found.');
                }

                $savingsAccount = null;

                // --- Repayment from savings: verify funds before touching anything ---
                if ($payFromSavings) {
                    $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
                        ->where('client_id', $loan->client_id)
                        ->where('account_type', 'savings')
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->first();

                    if (!$savingsAccount) {
                        throw new \Exception('This client has no active savings account to pay from.');
                    }

                    $available = $savingsAccount->balance - $savingsAccount->lien_amount;

                    if ($totalAmount > $available) {
                        throw new \Exception(
                            'Insufficient savings balance. Available to withdraw: ' .
                            number_format($available) .
                            ($savingsAccount->lien_amount > 0 ? ' (' . number_format($savingsAccount->lien_amount) . ' locked as loan collateral).' : '.')
                        );
                    }
                }

                $newPayment = Payment::create([
                    'loan_id'        => $validated['loan_id'],
                    'payment_date'   => $validated['payment_date'],
                    'amount_paid'    => $totalAmount,
                    'principal_paid' => $validated['principal_paid'],
                    'interest_paid'  => $validated['interest_paid'],
                    'payment_method' => $payFromSavings ? 'Savings Wallet' : $validated['payment_method'],
                    'receipt_number' => $receiptNumber,
                    'notes'          => $validated['notes'] ?? null,
                ]);

                // Cash-funded repayments bring new cash in the door; wallet
                // repayments just move an existing liability (savings) down
                // against the loan portfolio — no cash line for those.
                $repaymentLines = [
                    ['code' => $payFromSavings ? '2000' : '1000', 'debit' => $totalAmount, 'description' => $payFromSavings ? 'Debited from savings' : 'Cash received'],
                    ['code' => '1100', 'credit' => $validated['principal_paid'], 'description' => 'Principal repaid'],
                    ['code' => '4000', 'credit' => $validated['interest_paid'], 'description' => 'Interest income'],
                ];
                $repaymentEntry = JournalPoster::post($managerId, 'Loan repayment — receipt ' . $receiptNumber, 'loan_repayment', $repaymentLines, $receiptNumber);

                if ($repaymentEntry) {
                    $newPayment->update(['journal_entry_id' => $repaymentEntry->id]);
                }

                if ($payFromSavings) {
                    $savingsAccount->decrement('balance', $totalAmount);

                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $loan->client_id,
                        'mfi_account_id' => $savingsAccount->id,
                        'transaction_type' => 'withdrawal',
                        'amount' => $totalAmount,
                        'credit' => 0,
                        'debit' => $totalAmount,
                        'transaction_date' => $validated['payment_date'],
                        'payment_method' => 'Savings Wallet',
                        'reference_number' => $receiptNumber,
                        'narration' => 'Loan repayment paid from savings (Receipt: ' . $receiptNumber . ')',
                    ]);
                }

                // --- Compulsory savings auto-split ---
                // Only on cash-funded repayments: paying from savings and then
                // immediately re-depositing a slice back into that same wallet
                // would be a no-op.
                $compulsorySplit = 0;
                if ($isMfi && !$payFromSavings && $loan->mfi_loan_product_id) {
                    $product = MfiProduct::find($loan->mfi_loan_product_id);
                    $percent = $product ? $product->compulsory_savings_percent : 0;

                    if ($percent > 0) {
                        $splitAmount = round($totalAmount * $percent / 100, 2);

                        if ($splitAmount > 0) {
                            $compulsoryAccount = $this->resolveSavingsAccount($managerId, $loan->client_id);
                            $compulsoryAccount->increment('balance', $splitAmount);

                            MfiTransaction::create([
                                'loan_manager_id' => $managerId,
                                'client_id' => $loan->client_id,
                                'mfi_account_id' => $compulsoryAccount->id,
                                'transaction_type' => 'deposit',
                                'amount' => $splitAmount,
                                'credit' => $splitAmount,
                                'debit' => 0,
                                'transaction_date' => $validated['payment_date'],
                                'payment_method' => $validated['payment_method'],
                                'narration' => 'Compulsory savings on loan repayment (Receipt: ' . $receiptNumber . ')',
                            ]);

                            JournalPoster::post($managerId, 'Compulsory savings top-up — receipt ' . $receiptNumber, 'compulsory_savings', [
                                ['code' => '1000', 'debit' => $splitAmount, 'description' => 'Cash received'],
                                ['code' => '2000', 'credit' => $splitAmount, 'description' => 'Compulsory savings top-up'],
                            ], $receiptNumber);

                            $compulsorySplit = $splitAmount;
                        }
                    }
                }

                // --- Loan payoff + collateral release ---
                // Uses the same lien-release logic as LoanController's manual
                // status toggle, so a loan paid off through the normal
                // repayment screen also frees up any locked savings collateral.
                $totalDue = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100));
                $paidSoFar = $loan->payments()->sum('amount_paid');

                if ($paidSoFar >= $totalDue && $loan->status !== 'paid') {
                    if ($isMfi && $loan->collateral_locked > 0) {
                        $this->releaseLoanCollateral($loan, $managerId);
                    }
                    $loan->status = 'paid';
                    $loan->save();
                }

                return ['payment' => $newPayment, 'compulsory_split' => $compulsorySplit];
            });

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
     * Find the client's active savings account, or auto-provision a default
     * one if they don't have one yet (mirrors SavingsController's fallback
     * so the compulsory savings split always has somewhere to land).
     */
    private function resolveSavingsAccount(int $managerId, int $clientId): MfiAccount
    {
        $account = MfiAccount::where('loan_manager_id', $managerId)
            ->where('client_id', $clientId)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if ($account) {
            return $account;
        }

        $product = MfiProduct::where('loan_manager_id', $managerId)
            ->where('product_type', 'savings')
            ->first();

        $productId = $product?->id ?? MfiProduct::create([
            'loan_manager_id' => $managerId,
            'name' => 'Standard Daily Savings',
            'product_type' => 'savings',
            'interest_rate' => 0,
            'rules' => ['minimum_balance' => 0, 'is_compulsory' => false, 'allow_withdrawals' => true],
            'is_active' => true,
        ])->id;

        return MfiAccount::create([
            'loan_manager_id' => $managerId,
            'client_id' => $clientId,
            'mfi_product_id' => $productId,
            'account_number' => 'SAV-' . time() . rand(10, 99),
            'account_type' => 'savings',
            'balance' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Release the exact amount of collateral this loan locked, back onto the
     * client's savings account. Mirrors LoanController::releaseLoanCollateral
     * so a loan paid off via the normal repayment screen (not just the
     * separate status-toggle button) also frees its lien. Caller is
     * responsible for saving $loan afterwards.
     */
    private function releaseLoanCollateral(Loan $loan, int $managerId): void
    {
        $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
            ->where('client_id', $loan->client_id)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if ($savingsAccount) {
            $release = min($loan->collateral_locked, $savingsAccount->lien_amount);
            $savingsAccount->decrement('lien_amount', $release);
        }

        $loan->collateral_locked = 0;
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
                $totalDue = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100));
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