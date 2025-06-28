<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Client;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
        // Start with the relationship query, which we know works.
        $query = Auth::user()->loans()->with('client'); // Eager load client info for display

        // Check if a search term was submitted
        if ($search = $request->input('search')) {
            // Add the case-insensitive search on the related client's name
            $query->whereHas('client', function($subQuery) use ($search) {
                $searchTerm = strtolower($search);
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        // Now, execute the final query
        $loans = $query->latest()->get();

        return view('loan-manager.loans.index', compact('loans'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Auth::user()->clients;
        return view('loan-manager.loans.create', compact('clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'principal_amount' => 'required|numeric|min:0',
            'processing_fee' => 'nullable|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'start_date' => 'required|date',
        ]);

        $loan = Loan::create(array_merge($validatedData, [
            'loan_manager_id' => Auth::id(),
            'status' => 'active',
            'repayment_frequency' => 'monthly',
        ]));

        $this->recordLoanDisbursement($loan);
        return redirect()->route('loans.index')->with('status', 'New loan has been created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) {
            abort(403);
        }
        $loan->load('payments', 'guarantors', 'collaterals');

        $principal = $loan->principal_amount;
        $monthlyInterestRate = ($loan->interest_rate / 100) / 12;
        $termInMonths = $loan->term;
        $schedule = [];

        if ($denominator = (pow(1 + $monthlyInterestRate, $termInMonths) - 1)) {
            $numerator = $monthlyInterestRate * pow(1 + $monthlyInterestRate, $termInMonths);
            $monthlyPayment = $principal * ($numerator / $denominator);
            
            $remainingBalance = $principal;
            $startDate = Carbon::parse($loan->start_date);
            for ($month = 1; $month <= $termInMonths; $month++) {
                $interestComponent = $remainingBalance * $monthlyInterestRate;
                $principalComponent = $monthlyPayment - $interestComponent;
                $remainingBalance -= $principalComponent;
                if ($month == $termInMonths) {
                    $monthlyPayment += $remainingBalance;
                    $principalComponent += $remainingBalance;
                    $remainingBalance = 0;
                }
                $schedule[] = ['month' => $month, 'due_date' => $startDate->copy()->addMonths($month)->toDateString(), 'payment_amount' => $monthlyPayment, 'principal' => $principalComponent, 'interest' => $interestComponent, 'remaining_balance' => $remainingBalance];
            }
        }
        
        return view('loan-manager.loans.show', compact('loan', 'schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) {
            abort(403);
        }
        return view('loan-manager.loans.edit', compact('loan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) {
            abort(403);
        }
        $validatedData = $request->validate([
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'status' => 'required|string|in:pending,active,paid,defaulted',
        ]);
        $loan->update($validatedData);
        return redirect()->route('loans.show', $loan->id)->with('status', 'Loan details have been updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) {
            abort(403);
        }
        $loan->delete();
        return redirect()->route('loans.index')->with('status', 'Loan has been deleted successfully.');
    }

    /**
     * Helper method to record the double-entry transaction for a loan disbursement.
     */
    private function recordLoanDisbursement(Loan $loan)
    {
        $loansReceivableAccount = Account::where('name', 'Loans Receivable')->first();
        $cashOnHandAccount = Account::where('name', 'Cash on Hand')->first();

        if ($loansReceivableAccount && $cashOnHandAccount) {
            GeneralLedgerTransaction::create(['account_id' => $loansReceivableAccount->id, 'loan_id' => $loan->id, 'transaction_date' => $loan->start_date, 'description' => 'Loan disbursed to ' . $loan->client->name, 'debit' => $loan->principal_amount, 'credit' => 0]);
            GeneralLedgerTransaction::create(['account_id' => $cashOnHandAccount->id, 'loan_id' => $loan->id, 'transaction_date' => $loan->start_date, 'description' => 'Loan disbursed to ' . $loan->client->name, 'debit' => 0, 'credit' => $loan->principal_amount]);
        }
    }
}