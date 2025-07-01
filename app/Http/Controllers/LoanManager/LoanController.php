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
use Illuminate\Support\Facades\DB;
use App\Models\Guarantor;
use App\Models\Collateral;

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
        // 1. Validate all possible data from the form
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'principal_amount' => 'required|numeric|min:0',
            'processing_fee' => 'nullable|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'guarantor_first_name' => 'nullable|string|max:255',
            'guarantor_last_name' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_phone_number' => 'required_with:guarantor_first_name|string|max:20',
            'guarantor_address' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_relationship' => 'required_with:guarantor_first_name|string|max:100',
            'collateral_type' => 'nullable|string|max:100',
            'collateral_description' => 'required_with:collateral_type|string',
            'collateral_valuation_amount' => 'required_with:collateral_type|numeric|min:0',
        ]);

        // Use a database transaction to ensure everything saves successfully, or nothing does.
        DB::transaction(function () use ($validatedData, $request) {
            // 2. Create the Loan first, making sure to include the client_id
            $loan = Loan::create([
                'client_id' => $validatedData['client_id'], // <-- THIS IS THE FIX
                'loan_manager_id' => Auth::id(),
                'principal_amount' => $validatedData['principal_amount'],
                'processing_fee' => $validatedData['processing_fee'] ?? 0,
                'interest_rate' => $validatedData['interest_rate'],
                'term' => $validatedData['term'],
                'start_date' => $validatedData['start_date'],
                'status' => 'active',
            ]);

            // 3. If Guarantor details were provided, create the Guarantor
            if ($request->filled('guarantor_first_name')) {
                $loan->guarantors()->create([
                    'first_name' => $validatedData['guarantor_first_name'],
                    'last_name' => $validatedData['guarantor_last_name'],
                    'phone_number' => $validatedData['guarantor_phone_number'],
                    'address' => $validatedData['guarantor_address'],
                    'relationship_to_borrower' => $validatedData['guarantor_relationship'],
                ]);
            }

            // 4. If Collateral details were provided, create the Collateral
            if ($request->filled('collateral_type')) {
                $loan->collaterals()->create([
                    'collateral_type' => $validatedData['collateral_type'],
                    'description' => $validatedData['collateral_description'],
                    'valuation_amount' => $validatedData['collateral_valuation_amount'],
                ]);
            }

            // 5. Record the accounting transaction
            $this->recordLoanDisbursement($loan);
        });

        // 6. Redirect with a success message
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