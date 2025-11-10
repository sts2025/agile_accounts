<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\LoanManager; // For dynamic currency
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    // --- CREATE (Assuming you have this) ---
    public function create(Request $request)
    {
        $manager = Auth::user()->loanManager;
        
        // Eager load client info for the dropdown
        $loans = $manager->loans()->where('status', 'active')->with('client')->get();
        
        // Pre-select loan if ID is in the URL
        $selectedLoanId = $request->query('loan_id');

        return view('loan-manager.payments.create', compact('loans', 'selectedLoanId'));
    }

    
    // --- STORE PAYMENT (FIXED) ---
    public function store(Request $request)
    {
        $loan = Loan::findOrFail($request->loan_id);
        $managerId = Auth::user()->loanManager->id;
        $currency = LoanManager::getCurrency();
        
        // 1. Security Check: Ensure this loan belongs to this manager
        if ($loan->loan_manager_id != $managerId) {
             abort(403, 'Loan access forbidden.');
        }

        // 2. Validation
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount_paid' => 'required|numeric|min:1', 
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $paymentAmount = $validated['amount_paid'];
        $remainingBalance = $loan->balance();

        // 3. OVERPAYMENT CHECK
        if ($paymentAmount > $remainingBalance) {
            return back()->withInput()->with('error', 
                "Payment failed: Amount ({$currency} " . number_format($paymentAmount) . 
                ") exceeds the remaining balance ({$currency} " . number_format($remainingBalance) . ")."
            );
        }

        // *** FIX 1: Define $payment variable outside the transaction scope ***
        $payment = null;

        // --- 4. TRANSACTION AND SAVE ---
        try {
            // *** FIX 2: Pass $payment by reference (&) into the closure ***
            DB::transaction(function () use ($validated, $loan, $paymentAmount, $remainingBalance, &$payment) {
                
                // Create the Payment record
                // *** FIX 3: Assign to the $payment variable from the outer scope ***
                $payment = Payment::create([
                    'loan_id' => $validated['loan_id'],
                    'amount_paid' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] ?? null,
                    'receipt_number' => 'RCP-' . time(),
                ]);

                // Update loan status if fully paid
                if ($remainingBalance - $paymentAmount <= 0.01) {
                    $loan->update(['status' => 'paid']);
                }
                
                // Note: General Ledger entry would go here
            });
            
            // *** NOW IT WORKS: $payment is available here ***
            return redirect()->route('payments.receipt', $payment->id)
                ->with('success', 'Payment recorded successfully!');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Payment failed for Loan ID {$loan->id}: " . $e->getMessage());
            return back()->withInput()->with('error', 'A server error occurred while recording payment. Please try again.');
        }
    }
    
    
    // --- SHOW RECEIPT (Unchanged) ---
    public function showReceipt(Payment $payment)
    {
         // Ensure the payment belongs to the logged-in manager
         if ($payment->loan->loan_manager_id !== auth()->user()->loanManager->id) {
             abort(403);
         }

         $loan = $payment->loan;

         // Calculate the total amounts
         $totalRepayable = $loan->totalRepayable();
         $totalPaid = $loan->payments()->sum('amount_paid');
         $loanBalance = $loan->balance();

         // Point to your thermal receipt view
         return view('loan-manager.payments.receipt-thermal', [
             'payment' => $payment,
             'loan' => $loan,
             'loanBalance' => $loanBalance,
             'totalRepayable' => $totalRepayable,
             'totalPaid' => $totalPaid,
         ]);
    }
}