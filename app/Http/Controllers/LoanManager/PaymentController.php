<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Loan;
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
        // 1. CORRECT VALIDATION: Only put Laravel rules in here
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
            // Assign the DB transaction to the $payment variable
            $payment = DB::transaction(function () use ($validated, $totalAmount) {
                
                // 2. LOGIC FIX: Map the reference_id to receipt_number
                // If they typed something, use it. If not, auto-generate it.
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
                    // Note: We DO NOT pass 'reference_id' here so it doesn't crash the database!
                    'receipt_number' => $receiptNumber,
                    'notes'          => $validated['notes'] ?? null,
                ]);

                // Check if loan is fully paid
                $loan = Loan::find($validated['loan_id']);
                $totalDue = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100));
                $paidSoFar = $loan->payments()->sum('amount_paid');
                
                if ($paidSoFar >= $totalDue) {
                    $loan->update(['status' => 'paid']);
                }

                return $newPayment; // Important: Return the newly created payment
            });

            // Redirect directly to the thermal receipt!
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