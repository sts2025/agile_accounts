<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashTransactionController extends Controller
{
    /**
     * Display a listing of the resource (Payables & Receivables).
     */
    public function index(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        // Default date range
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $startDate = $request->input('start_date', Carbon::today()->subMonth(1)->toDateString());

        // Fetch transactions for the manager within the date range
        $transactions = CashTransaction::where('loan_manager_id', $managerId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get();
            
        // Note: I am intentionally leaving out the aggregation/summary logic here 
        // as the view calculates totals during the loop.

        return view('loan-manager.transactions.cash-transactions.index', [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
    
    /**
     * Store a newly created resource (Assuming this uses the dashboard modal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|string|in:payable,receivable',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date|before_or_equal:today',
        ]);
        
        $managerId = Auth::user()->loanManager->id;

        CashTransaction::create(array_merge($validated, [
            'loan_manager_id' => $managerId,
        ]));

        return back()->with('success', 'Payable/Receivable recorded successfully.');
    }

    // Add other methods (show, edit, update, destroy) as needed
}