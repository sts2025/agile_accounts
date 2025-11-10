<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\BankTransaction; 

class BankTransactionController extends Controller
{
    /**
     * Display a listing of the bank transactions for the manager.
     * This is the function for your report page.
     */
    public function index(Request $request)
    {
        $manager = Auth::user()->loanManager;
        $query = $manager->bankTransactions(); 

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        if ($startDate && $endDate) {
            // *** THE FIX: Filter by 'deposit_date' not 'transaction_date' ***
            $query->whereBetween('deposit_date', [$startDate, $endDate]);
        }

        $transactions = $query->latest()->get();

        return view('loan-manager.transactions.bank-transactions.index', [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Store a newly created bank transaction in storage.
     * This is the function for your "Add Banking" modal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:Deposit,Withdrawal',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
        ]);

        $manager = Auth::user()->loanManager;

        // *** THE FIX: We must manually map the form fields to the
        //     database columns because the names are different.
        $manager->bankTransactions()->create([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'deposit_date' => $validated['transaction_date'], // Map form 'transaction_date' to DB 'deposit_date'
            // 'loan_manager_id' is added automatically by the relationship
        ]);

        // Redirect back to the report page to show the new transaction
        return redirect()->route('bank-transactions.index')->with('success', 'Bank transaction recorded.');
    }
}