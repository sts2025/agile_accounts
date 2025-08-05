<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = Auth::user()->expenses()->latest()->get();
        return view('loan-manager.expenses.index', compact('expenses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $expense = Auth::user()->expenses()->create($validated);

        // Accounting Entries
        $cashAccount = Account::where('name', 'Cash on Hand')->firstOrFail();
        $expenseAccount = Account::firstOrCreate(['name' => $expense->description, 'type' => 'Expense']);
        GeneralLedgerTransaction::create(['account_id' => $cashAccount->id, 'transaction_date' => $expense->expense_date, 'description' => 'Expense: '.$expense->description, 'debit' => 0, 'credit' => $expense->amount]);
        GeneralLedgerTransaction::create(['account_id' => $expenseAccount->id, 'transaction_date' => $expense->expense_date, 'description' => 'Expense: '.$expense->description, 'debit' => $expense->amount, 'credit' => 0]);

        return redirect()->route('dashboard')->with('status', 'Expense recorded successfully!');
    }

    /**
     * Generate a PDF of all expenses.
     */
    public function downloadPdf()
    {
        $expenses = Auth::user()->expenses()->latest()->get();
        $pdf = Pdf::loadView('reports.pdf.expenses', compact('expenses'));
        return $pdf->stream('expenses-report.pdf');
    }
}