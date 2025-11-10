<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
// We don't need to import ExpenseCategory here, but we will load the relationship

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        // Get dates from request, default to current month
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $expenses = Expense::where('loan_manager_id', $managerId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            // *** THIS IS THE FIX for the report ***
            // Eager load the 'category' relationship to get the name
            ->with('category') 
            ->get(); // Removed orderBy for safety

        return view('loan-manager.transactions.expenses.index', [
            'expenses' => $expenses,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // *** THIS IS THE FIX for saving ***
        // We now validate 'expense_category_id' (which is a number)
        $validated = $request->validate([
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        $managerId = Auth::user()->loanManager->id;

        // Create the expense using the validated data
        Expense::create([
            'loan_manager_id' => $managerId,
            'expense_category_id' => $validated['expense_category_id'], // <-- Save the ID
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
        ]);

        // Redirect to the index page to show the report
        // We pass the date of the transaction to filter the report
        return redirect()->route('expenses.index', [
            'start_date' => Carbon::parse($validated['expense_date'])->startOfMonth()->toDateString(),
            'end_date' => Carbon::parse($validated['expense_date'])->endOfMonth()->toDateString(),
        ])->with('success', 'Expense recorded successfully!');
    }
}

