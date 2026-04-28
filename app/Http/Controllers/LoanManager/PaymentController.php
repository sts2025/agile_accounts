<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Loan;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $manager = Auth::user()->loanManager;
        
        $payments = Payment::whereHas('loan', function($q) use ($manager) {
            $q->where('loan_manager_id', $manager->id);
        })->with('loan.client')->latest('payment_date')->paginate(15);
        
        // Fetch active loans for the modal dropdown
        $loans = $manager->loans()->where('status', 'active')->with('client', 'payments')->get();

        return view('loan-manager.payments.index', compact('payments', 'loans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id'        => 'required|exists:loans,id',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid'  => 'required|numeric|min:0',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
            'reference_id'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
        ]);

        // Auto-calculate total amount based on the split
        $totalAmount = $validated['principal_paid'] + $validated['interest_paid'];

        if ($totalAmount <= 0) {
            return back()->with('error', 'Payment amount must be greater than zero.');
        }

        try {
            $payment = DB::transaction(function () use ($validated, $totalAmount) {
                
                $receiptNumber = !empty($validated['reference_id']) 
                                    ? $validated['reference_id'] 
                                    : 'RCP-' . time() . rand(10, 99);

                $newPayment = Payment::create([
                    'loan_id'        => $validated['loan_id'],
                    'payment_date'   => $validated['payment_date'],
                    'amount_paid'    => $totalAmount,
                    'principal_paid' => $validated['principal_paid'],
                    'interest_paid'  => $validated['interest_paid'],
                    'payment_method' => $validated['payment_method'],
                    'receipt_number' => $receiptNumber,
                    'notes'          => $validated['notes'] ?? null,
                ]);

                // Update Loan Status
                $loan = Loan::with('client')->find($validated['loan_id']);
                $totalDue = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100));
                $paidSoFar = $loan->payments()->sum('amount_paid');
                
                if ($paidSoFar >= $totalDue) {
                    $loan->update(['status' => 'paid']);
                }

                // --- NEW FIX: RECORD IN GENERAL LEDGER ---
                $cashAccount = Account::where('name', 'Cash on Hand')->first();
                $loanReceivableAccount = Account::where('name', 'Loans Receivable')->first();
                $interestIncomeAccount = Account::where('name', 'Interest Income')->first();

                if ($cashAccount) {
                    // 1. Debit Cash (The full 70k hits Cash Flow)
                    GeneralLedgerTransaction::create([
                        'account_id' => $cashAccount->id,
                        'loan_id' => $loan->id,
                        'transaction_date' => $validated['payment_date'],
                        'description' => 'Loan Repayment from ' . ($loan->client->name ?? 'Client') . ' (Receipt: ' . $receiptNumber . ')',
                        'debit' => $totalAmount,
                        'credit' => 0
                    ]);

                    // 2. Credit Loans Receivable (Only the 40k Principal)
                    if ($loanReceivableAccount && $validated['principal_paid'] > 0) {
                        GeneralLedgerTransaction::create([
                            'account_id' => $loanReceivableAccount->id,
                            'loan_id' => $loan->id,
                            'transaction_date' => $validated['payment_date'],
                            'description' => 'Principal Repayment from ' . ($loan->client->name ?? 'Client'),
                            'debit' => 0,
                            'credit' => $validated['principal_paid']
                        ]);
                    }

                    // 3. Credit Interest Income (Only the 30k Interest hits Profit)
                    if ($interestIncomeAccount && $validated['interest_paid'] > 0) {
                        GeneralLedgerTransaction::create([
                            'account_id' => $interestIncomeAccount->id,
                            'loan_id' => $loan->id,
                            'transaction_date' => $validated['payment_date'],
                            'description' => 'Interest Payment from ' . ($loan->client->name ?? 'Client'),
                            'debit' => 0,
                            'credit' => $validated['interest_paid']
                        ]);
                    }
                }

                return $newPayment; 
            });

            return redirect()->route('payments.receipt', $payment->id)
                             ->with('success', 'Payment recorded successfully!');
                             
        } catch (\Exception $e) {
            return back()->with('error', 'Database Error: ' . $e->getMessage());
        }
    }

    public function showReceipt(Payment $payment) 
    { 
        return view('loan-manager.payments.receipt-thermal', compact('payment')); 
    }

    public function edit(Payment $payment) 
    { 
        return view('loan-manager.payments.edit', compact('payment')); 
    }

    public function update(Request $request, Payment $payment) 
    { 
        return back()->with('success', 'Payment Updated'); 
    }

    public function destroy(Payment $payment) 
    { 
        $payment->delete(); 
        return back()->with('success', 'Payment Deleted'); 
    }
}