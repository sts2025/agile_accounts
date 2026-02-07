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
        // FIX 1: Use Loan Manager Profile ID (matches Dashboard logic)
        $managerId = Auth::user()->loanManager->id;

        // Default date range
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $startDate = $request->input('start_date', Carbon::today()->subMonth(1)->toDateString());

        // Fetch transactions for the manager profile
        $transactions = CashTransaction::where('loan_manager_id', $managerId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->paginate(15); // Added pagination for better performance

        return view('loan-manager.transactions.cash-transactions.index', [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
    
    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        // FIX 1: Get Correct Profile ID
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            // Allow all terms during validation, we map them below
            'type' => 'required|string|in:payable,receivable,inflow,outflow', 
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
        ]);
        
        // FIX 2: Map Types to Match Dashboard Logic
        // Payable (Money OUT) -> outflow
        // Receivable (Money IN) -> inflow
        $type = $validated['type'];
        if ($type === 'payable') $type = 'outflow';
        if ($type === 'receivable') $type = 'inflow';

        CashTransaction::create([
            'loan_manager_id' => $managerId,   // Correct Profile Link
            'description' => $validated['description'],
            'type' => $type,                   // Corrected Type
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
        ]);

        return back()->with('success', 'Transaction recorded successfully.');
    }
}