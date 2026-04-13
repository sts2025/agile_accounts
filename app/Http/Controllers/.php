<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Loan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        // Fetch payments
        $payments = Payment::whereHas('loan', function($q) use ($managerId) {
            $q->where('loan_manager_id', $managerId);
        })->with(['loan.client'])->latest()->get();

        // Fetch active loans
        $loans = Loan::where('loan_manager_id', $managerId)
                     ->where('status', '!=', 'completed')
                     ->with('client')
                     ->get();

        // --- FORCED VIEW LOAD ---
        // We are removing the "if exists" check.
        // We are telling Laravel: "Load THIS specific file or crash trying."
        // This confirms if your folder is named correctly.
        return view('loan-manager.payments.index', compact('payments', 'loans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid' => 'required|numeric|min:0',
            'date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference_id' => 'nullable|string',
        ]);

        $loan = Loan::findOrFail($request->loan_id);
        
        // Calculate Total
        $totalAmount = $request->principal_paid + $request->interest_paid;

        if ($totalAmount <= 0) {
            return back()->with('error', 'Total payment amount must be greater than zero.');
        }

        DB::transaction(function () use ($request, $loan, $totalAmount) {
            Payment::create([
                'loan_id' => $loan->id,
                'amount' => $totalAmount, 
                'principal_paid' => $request->principal_paid,
                'interest_paid' => $request->interest_paid,
                'payment_date' => $request->date,
                'payment_method' => $request->payment_method ?? 'Cash',
                'reference_id' => $request->reference_id,
                'collected_by' => Auth::id(),
            ]);

            $loan->current_balance = $loan->current_balance - $totalAmount;

            if ($loan->current_balance <= 0) {
                $loan->current_balance = 0;
                $loan->status = 'completed';
            }
            
            $loan->save();
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function show(string $id) { /* Optional */ }
    public function update(Request $request, string $id) { /* Optional */ }
    public function destroy(string $id) { /* Optional */ }
}