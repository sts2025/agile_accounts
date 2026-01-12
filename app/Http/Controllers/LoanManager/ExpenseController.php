<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory; // Imported Model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $expenses = Expense::where('loan_manager_id', $managerId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category') 
            ->latest('expense_date') // Better sorting
            ->paginate(15); // Added pagination for better performance

        // Note: Ensure your view folder structure matches this path
        return view('loan-manager.expenses.index', [
            'expenses' => $expenses,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Show the form for creating a new expense.
     * Needed to populate the dropdown/datalist.
     */
    public function create()
    {
        $manager = Auth::user()->loanManager;
        // Fetch existing categories to show in the dropdown list
        $categories = ExpenseCategory::where('loan_manager_id', $manager->id)->orderBy('name')->get();
        
        return view('loan-manager.expenses.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        // *** FIX: Validate NAME, not ID ***
        // We allow 'category_name' to be a string so you can type anything.
        $validated = $request->validate([
            'category_name' => 'required|string|max:255', // Changed from ID validation
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        // *** SMART CATEGORY LOGIC ***
        // 1. Check if category exists for this manager.
        // 2. If yes, use it. If no, create it.
        $category = ExpenseCategory::firstOrCreate(
            [
                'loan_manager_id' => $managerId,
                'name' => trim($validated['category_name']) // Trim whitespace
            ]
        );

        // Create the expense
        Expense::create([
            'loan_manager_id' => $managerId,
            'expense_category_id' => $category->id, // Use the ID from the found/created category
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('expenses.index', [
            'start_date' => Carbon::parse($validated['expense_date'])->startOfMonth()->toDateString(),
            'end_date' => Carbon::parse($validated['expense_date'])->endOfMonth()->toDateString(),
        ])->with('success', 'Expense recorded successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        if ($expense->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403);
        }
        
        $categories = ExpenseCategory::where('loan_manager_id', $expense->loan_manager_id)->orderBy('name')->get();
        return view('loan-manager.expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        if ($expense->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403);
        }

        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        // Smart Category Update
        $category = ExpenseCategory::firstOrCreate(
            [
                'loan_manager_id' => $expense->loan_manager_id,
                'name' => trim($validated['category_name'])
            ]
        );

        $expense->update([
            'expense_date' => $validated['expense_date'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'expense_category_id' => $category->id
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403);
        }
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}