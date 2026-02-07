<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BankTransactionController extends Controller
{
    /**
     * Display a listing of the bank transactions.
     */
    public function index(Request $request)
    {
        // FIX: Must use Loan Manager Profile ID, NOT User ID
        $managerId = Auth::user()->loanManager->id;

        $query = BankTransaction::where('loan_manager_id', $managerId);

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        if ($startDate && $endDate) {
            $query->whereBetween('deposit_date', [$startDate, $endDate]);
        }

        $transactions = $query->latest('deposit_date')->paginate(15);

        return view('loan-manager.transactions.bank-transactions.index', [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Store a newly created bank transaction.
     */
    public function store(Request $request)
    {
        // FIX: Must use Loan Manager Profile ID
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'type' => 'required|string|in:Deposit,Withdrawal', // Matches Dashboard Logic
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
        ]);

        BankTransaction::create([
            'loan_manager_id' => $managerId, // CRITICAL: Links to Dashboard Profile
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'deposit_date' => $validated['transaction_date'],
            'bank_name' => 'N/A', // Default if form doesn't have it
        ]);

        return redirect()->route('bank-transactions.index')
            ->with('success', 'Bank transaction recorded successfully.');
    }
}