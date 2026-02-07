<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $expenses = Expense::where('loan_manager_id', $managerId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category') 
            ->latest('expense_date') 
            ->paginate(15); 

        // --- THE FIX IS HERE ---
        // We fetch the categories so the "Add Expense" modal can use them
        $categories = ExpenseCategory::where('loan_manager_id', $managerId)->orderBy('name')->get();

        return view('loan-manager.expenses.index', [
            'expenses' => $expenses,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'categories' => $categories // Passing the variable that was undefined
        ]);
    }

    /**
     * Show the form for creating a new expense (Standalone page).
     */
    public function create()
    {
        $manager = Auth::user()->loanManager;
        $categories = ExpenseCategory::where('loan_manager_id', $manager->id)->orderBy('name')->get();
        
        return view('loan-manager.expenses.create', compact('categories'));
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'category_name'       => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0',
            'expense_date'        => 'required|date',
            'description'         => 'nullable|string|max:500',
        ]);

        // Logic to use ID if selected, or Name if typed
        $categoryId = $validated['expense_category_id'] ?? null;

        if (!$categoryId && !empty($validated['category_name'])) {
            $category = ExpenseCategory::firstOrCreate(
                ['loan_manager_id' => $managerId, 'name' => trim($validated['category_name'])]
            );
            $categoryId = $category->id;
        }

        if (!$categoryId) {
            return back()->withErrors(['category_name' => 'Please select a category or type a new one.']);
        }

        Expense::create([
            'loan_manager_id'     => $managerId,
            'expense_category_id' => $categoryId,
            'amount'              => $validated['amount'],
            'expense_date'        => $validated['expense_date'],
            'description'         => $validated['description'] ?? null,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    /**
     * Show the form for editing the expense.
     */
    public function edit(Expense $expense)
    {
        if ($expense->loan_manager_id !== Auth::user()->loanManager->id) { abort(403); }
        
        $categories = ExpenseCategory::where('loan_manager_id', $expense->loan_manager_id)->orderBy('name')->get();
        return view('loan-manager.expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update the expense.
     */
    public function update(Request $request, Expense $expense)
    {
        if ($expense->loan_manager_id !== Auth::user()->loanManager->id) { abort(403); }

        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'category_name'       => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0',
            'expense_date'        => 'required|date',
            'description'         => 'nullable|string|max:500',
        ]);

        $categoryId = $validated['expense_category_id'] ?? null;

        if (!$categoryId && !empty($validated['category_name'])) {
            $category = ExpenseCategory::firstOrCreate(
                ['loan_manager_id' => $expense->loan_manager_id, 'name' => trim($validated['category_name'])]
            );
            $categoryId = $category->id;
        }

        if (!$categoryId) {
            $categoryId = $expense->expense_category_id;
        }

        $expense->update([
            'expense_date'        => $validated['expense_date'],
            'amount'              => $validated['amount'],
            'description'         => $validated['description'],
            'expense_category_id' => $categoryId
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->loan_manager_id !== Auth::user()->loanManager->id) { abort(403); }
        
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}