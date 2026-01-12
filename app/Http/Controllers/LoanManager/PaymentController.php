<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // --- LIST PAYMENTS ---
    public function index()
    {
        $manager = Auth::user()->loanManager;
        $payments = Payment::whereHas('loan', function($q) use ($manager) {
            $q->where('loan_manager_id', $manager->id);
        })->with('loan.client')->latest()->paginate(15);

        return view('loan-manager.payments.index', compact('payments'));
    }

    // --- SHOW CREATE FORM ---
    public function create(Request $request)
    {
        $manager = Auth::user()->loanManager;
        
        // Eager load client info, only show loans that aren't fully closed/paid
        $loans = $manager->loans()
                         ->where('status', '!=', 'paid')
                         ->with('client')
                         ->get();
        
        $selectedLoanId = $request->query('loan_id');

        return view('loan-manager.payments.create', compact('loans', 'selectedLoanId'));
    }

    // --- STORE PAYMENT ---
    public function store(Request $request)
    {
        $manager = Auth::user()->loanManager;
        $currency = $manager->currency_symbol ?? 'UGX';

        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount_paid' => 'required|numeric|min:1', 
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $loan = Loan::findOrFail($validated['loan_id']);
        
        // 1. Security Check
        if ($loan->loan_manager_id != $manager->id) {
             abort(403, 'Unauthorized: Loan access forbidden.');
        }

        // 2. Calculate Balance (Robust Method)
        $principal = $loan->principal_amount;
        $interest = $principal * ($loan->interest_rate / 100);
        $totalRepayable = $principal + $interest;
        $totalPaid = $loan->payments()->sum('amount_paid');
        $remainingBalance = round($totalRepayable - $totalPaid, 2);
        
        $paymentAmount = $validated['amount_paid'];

        // 3. Overpayment Check
        if ($paymentAmount > ($remainingBalance + 100)) { // Small buffer for rounding differences
            return back()->withInput()->with('error', 
                "Payment failed: Amount ({$currency} " . number_format($paymentAmount) . 
                ") exceeds the remaining balance ({$currency} " . number_format($remainingBalance) . ")."
            );
        }

        $payment = null;

        try {
            DB::transaction(function () use ($validated, $loan, $paymentAmount, $remainingBalance, &$payment) {
                
                // Create Payment
                $payment = Payment::create([
                    'loan_id' => $validated['loan_id'],
                    'amount_paid' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] ?? null,
                    'receipt_number' => 'RCP-' . strtoupper(uniqid()), // Using uniqid is safer than time() for collisions
                ]);

                // Update Status
                // If remaining balance after this payment is zero or less, mark paid
                if (($remainingBalance - $paymentAmount) <= 0) {
                    $loan->update(['status' => 'paid']);
                }
            });
            
            return redirect()->route('payments.receipt', $payment->id)
                ->with('success', 'Payment recorded successfully!');
            
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Server Error: ' . $e->getMessage());
        }
    }
    
    // --- SHOW RECEIPT ---
    public function showReceipt(Payment $payment)
    {
         if ($payment->loan->loan_manager_id !== auth()->user()->loanManager->id) {
             abort(403);
         }

         // Load relationships for the view
         $payment->load('loan.client', 'loan.loanManager');

         return view('loan-manager.payments.receipt-thermal', [
             'payment' => $payment
         ]);
    }

    // --- NEW: EDIT PAYMENT FORM ---
    public function edit(Payment $payment)
    {
        // Security Check
        if ($payment->loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        return view('loan-manager.payments.edit', compact('payment'));
    }

    // --- NEW: UPDATE PAYMENT LOGIC ---
    public function update(Request $request, Payment $payment)
    {
        // Security Check
        if ($payment->loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($payment, $validated) {
                // 1. Update the payment details
                $payment->update([
                    'amount_paid' => $validated['amount_paid'],
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes']
                ]);

                // 2. Re-evaluate Loan Status
                // We must recalculate everything because the amount might have changed
                $loan = $payment->loan;
                
                $principal = $loan->principal_amount;
                $interest = $principal * ($loan->interest_rate / 100);
                $totalDue = $principal + $interest;
                
                // Sum all payments (including the one we just updated)
                $totalPaid = $loan->payments()->sum('amount_paid');

                if ($totalPaid >= $totalDue) {
                    // If fully paid, ensure status is paid
                    if ($loan->status !== 'paid') {
                        $loan->update(['status' => 'paid']);
                    }
                } else {
                    // If balance remains, ensure status is NOT paid
                    if ($loan->status === 'paid') {
                        $loan->update(['status' => 'active']);
                    }
                }
            });

            return redirect()->route('payments.receipt', $payment->id)
                             ->with('success', 'Payment updated successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Update Failed: ' . $e->getMessage());
        }
    }
}