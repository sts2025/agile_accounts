<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

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
        
        if ($loan->loan_manager_id != $manager->id) {
             abort(403, 'Unauthorized: Loan access forbidden.');
        }

        // Calculate Balance
        $principal = $loan->principal_amount;
        $interest = $principal * ($loan->interest_rate / 100);
        $totalRepayable = $principal + $interest;
        $totalPaid = $loan->payments()->sum('amount_paid');
        $remainingBalance = round($totalRepayable - $totalPaid, 2);
        
        $paymentAmount = $validated['amount_paid'];

        // Overpayment Check
        if ($paymentAmount > ($remainingBalance + 100)) { 
            return back()->withInput()->with('error', 
                "Payment failed: Amount ({$currency} " . number_format($paymentAmount) . 
                ") exceeds the remaining balance ({$currency} " . number_format($remainingBalance) . ")."
            );
        }

        $payment = null;

        try {
            DB::transaction(function () use ($validated, $loan, $paymentAmount, $remainingBalance, &$payment) {
                
                $payment = Payment::create([
                    'loan_id' => $validated['loan_id'],
                    'amount_paid' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] ?? null,
                    'receipt_number' => 'RCP-' . strtoupper(uniqid()), 
                ]);

                // Update Loan Status
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
         if ($payment->loan->loan_manager_id !== Auth::user()->loanManager->id) {
             abort(403);
         }

         $payment->load('loan.client', 'loan.loanManager');

         // Ensure you have a view for this, otherwise redirect or dump
         if (view()->exists('loan-manager.payments.receipt-thermal')) {
             return view('loan-manager.payments.receipt-thermal', compact('payment'));
         }
         
         // Fallback if thermal view doesn't exist
         return redirect()->route('loans.show', $payment->loan_id)->with('success', 'Payment recorded.');
    }

    // --- EDIT PAYMENT FORM ---
    public function edit(Payment $payment)
    {
        if ($payment->loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        return view('loan-manager.payments.edit', compact('payment'));
    }

    // --- UPDATE PAYMENT LOGIC ---
    public function update(Request $request, Payment $payment)
    {
        if ($payment->loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:50', // Added validation for Receipt Number
        ]);

        try {
            DB::transaction(function () use ($payment, $validated) {
                
                // 1. Update Details
                $payment->update([
                    'amount_paid' => $validated['amount_paid'],
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'],
                    // Only update receipt_number if provided, else keep existing
                    'receipt_number' => $validated['receipt_number'] ?? $payment->receipt_number
                ]);

                // 2. Re-evaluate Loan Status
                $loan = $payment->loan;
                $principal = $loan->principal_amount;
                $interest = $principal * ($loan->interest_rate / 100);
                $totalDue = $principal + $interest;
                $totalPaid = $loan->payments()->sum('amount_paid');

                if ($totalPaid >= $totalDue) {
                    if ($loan->status !== 'paid') {
                        $loan->update(['status' => 'paid']);
                    }
                } else {
                    if ($loan->status === 'paid') {
                        $loan->update(['status' => 'active']);
                    }
                }
            });

            // Redirect back to Loan Details (Usually more useful after editing than the receipt)
            return redirect()->route('loans.show', $payment->loan_id)
                             ->with('success', 'Payment updated successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Update Failed: ' . $e->getMessage());
        }
    }

    // --- DESTROY PAYMENT ---
    public function destroy(Payment $payment)
    {
        if ($payment->loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        $loan = $payment->loan;
        
        DB::transaction(function() use ($payment, $loan) {
            $payment->delete();

            // Re-evaluate Loan Status after deletion
            $principal = $loan->principal_amount;
            $interest = $principal * ($loan->interest_rate / 100);
            $totalDue = $principal + $interest;
            $totalPaid = $loan->payments()->sum('amount_paid'); // This sum will now exclude the deleted payment

            if ($totalPaid < $totalDue && $loan->status === 'paid') {
                $loan->update(['status' => 'active']);
            }
        });

        return redirect()->route('loans.show', $loan->id)
            ->with('success', 'Payment deleted successfully.');
    }
}