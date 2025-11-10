<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Client;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use App\Models\Guarantor;
use App\Models\Collateral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->loanManager->loans()->with('client');

        if ($search = $request->input('search')) {
            $query->whereHas('client', function($subQuery) use ($search) {
                $searchTerm = strtolower($search);
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            });
        }
        $loans = $query->latest()->get();
        return view('loan-manager.loans.index', compact('loans'));
    }

    public function create()
    {
        $clients = Auth::user()->loanManager->clients;
        return view('loan-manager.loans.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $loanManagerId = Auth::user()->loanManager->id;

        // --- FINAL VALIDATION & SECURITY CHECKS ---
        $validatedData = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('loan_manager_id', $loanManagerId)],
            
            // Financial Checks
            'principal_amount' => 'required|numeric|min:100', // Principal must be sensible (>= 100)
            'processing_fee' => 'nullable|numeric|min:0', // Fee must be non-negative
            'interest_rate' => 'required|numeric|min:0|max:100', // Rate must be 0% to 100%
            
            // Term & Frequency Checks
            'term' => 'required|integer|min:1', 
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            
            // Date Check
            'start_date' => 'required|date|after_or_equal:today', // Must start today or in the future

            // Guarantor Checks
            'guarantor_first_name' => 'nullable|string|max:255',
            'guarantor_last_name' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_phone_number' => 'required_with:guarantor_first_name|string|max:20',
            'guarantor_address' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_occupation' => 'nullable|string|max:255',
            'guarantor_relationship' => 'required_with:guarantor_first_name|string|max:100',

            // Collateral Checks
            'collateral_type' => 'nullable|string|max:100',
            'collateral_description' => 'required_with:collateral_type|string',
            'collateral_valuation_amount' => 'required_with:collateral_type|numeric|min:1', // Valuation must be positive
        ]);

        DB::transaction(function () use ($validatedData, $request, $loanManagerId) {
            $loan = Loan::create([
                'client_id' => $validatedData['client_id'],
                'loan_manager_id' => $loanManagerId,
                'principal_amount' => $validatedData['principal_amount'],
                'processing_fee' => $validatedData['processing_fee'] ?? 0,
                'interest_rate' => $validatedData['interest_rate'],
                'term' => $validatedData['term'],
                'repayment_frequency' => $validatedData['repayment_frequency'],
                'start_date' => $validatedData['start_date'],
                'status' => 'active',
            ]);

            // Guarantor Creation Logic
            if ($request->filled('guarantor_first_name')) {
                $loan->guarantors()->create([
                    'first_name' => $validatedData['guarantor_first_name'],
                    'last_name' => $validatedData['guarantor_last_name'],
                    'phone_number' => $validatedData['guarantor_phone_number'],
                    'address' => $validatedData['guarantor_address'],
                    'occupation' => $validatedData['guarantor_occupation'] ?? null,
                    'relationship_to_borrower' => $validatedData['guarantor_relationship'],
                ]);
            }

            // Collateral Creation Logic
            if ($request->filled('collateral_type')) {
                $loan->collaterals()->create([
                    'collateral_type' => $validatedData['collateral_type'],
                    'description' => $validatedData['collateral_description'],
                    'valuation_amount' => $validatedData['collateral_valuation_amount'],
                ]);
            }
            
            // Record Disbursement to General Ledger
            $this->recordLoanDisbursement($loan);
        });

        return redirect()->route('loans.index')->with('success', 'New loan created and recorded successfully!');
    }

    // === NEW FEATURE: REPAYMENT CALCULATOR ===
    public function showCalculator(Request $request)
    {
        $schedule = [];
        $calculationPerformed = false;
        $totalInterest = 0;      // Initialize
        $totalRepayable = 0;     // Initialize
        
        // Default inputs (or use user input from form)
        $principal = $request->input('principal_amount', 1000000);
        $interestRate = $request->input('interest_rate', 10);
        $term = $request->input('term', 12);
        $frequency = $request->input('repayment_frequency', 'Monthly');

        if ($request->has('calculate')) {
            $calculationPerformed = true;
            
            if ($principal > 0 && $term > 0) {
                // Calculation assumes simple interest spread equally over term
                $totalInterest = $principal * ($interestRate / 100);
                $totalRepayable = $principal + $totalInterest;
                
                $paymentPerPeriod = $totalRepayable / $term;
                $principalComponent = $principal / $term;
                $interestComponent = $totalInterest / $term;
                
                $balance = $totalRepayable;
                $startDate = Carbon::today(); // Schedule always starts from today for projection

                for ($i = 1; $i <= $term; $i++) {
                    $balance -= $paymentPerPeriod;
                    $dueDate = $startDate->copy();
                    
                    // Determine the due date based on frequency
                    switch ($frequency) {
                        case 'Daily':   $dueDate->addDays($i);   break;
                        case 'Weekly':  $dueDate->addWeeks($i);  break;
                        default:        $dueDate->addMonths($i); break;
                    }
                    
                    $schedule[] = [
                        'period' => $i, 
                        'due_date' => $dueDate->toDateString(), 
                        'payment_amount' => $paymentPerPeriod, 
                        'principal' => $principalComponent, 
                        'interest' => $interestComponent, 
                        'balance' => ($i == $term) ? 0 : $balance
                    ];
                }
            }
        }

        // --- FINAL CONFIRMED VIEW PATH ---
        // This expects the file to be at resources/views/loan-manager/loans/calculator.blade.php
        
        // *** THE FIX: Pass all calculated variables to the view ***
        return view('loan-manager.loans.calculator', compact( 
            'schedule', 'principal', 'interestRate', 'term', 'frequency', 
            'calculationPerformed', 'totalRepayable', 'totalInterest' // <-- FIXED
        ));
    }


    public function show(Loan $loan)
    {
        // Authorize against the loanManager's ID
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        
        $loan->load('payments', 'guarantors', 'collaterals');

        $principal = $loan->principal_amount;
        $totalInterest = $principal * ($loan->interest_rate / 100);
        $totalRepayable = $principal + $totalInterest;
        $term = $loan->term > 0 ? $loan->term : 1;
        $paymentPerPeriod = $totalRepayable / $term;
        $principalComponent = $principal / $term;
        $interestComponent = $totalInterest / $term;
        $schedule = [];
        $balance = $totalRepayable;
        $startDate = Carbon::parse($loan->start_date);

        for ($i = 1; $i <= $term; $i++) {
            $balance -= $paymentPerPeriod;
            $dueDate = $startDate->copy();
            switch ($loan->repayment_frequency) {
                case 'Daily':   $dueDate->addDays($i);   break;
                case 'Weekly':  $dueDate->addWeeks($i);  break;
                default:        $dueDate->addMonths($i); break;
            }
            $schedule[] = [ 'period' => $i, 'due_date' => $dueDate->toDateString(), 'payment_amount' => $paymentPerPeriod, 'principal' => $principalComponent, 'interest' => $interestComponent, 'balance' => ($i == $term) ? 0 : $balance ];
        }
        
        return view('loan-manager.loans.show', compact('loan', 'schedule'));
    }

    public function edit(Loan $loan)
    {
        // Authorize against the loanManager's ID
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        return view('loan-manager.loans.edit', compact('loan'));
    }

    public function update(Request $request, Loan $loan)
    {
        // Authorize against the loanManager's ID
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        
        $validatedData = $request->validate([
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date',
            'status' => 'required|string|in:pending,active,paid,defaulted',
        ]);
        $loan->update($validatedData);
        return redirect()->route('loans.show', $loan->id)->with('status', 'Loan details have been updated successfully!');
    }

    public function destroy(Loan $loan)
    {
        // Authorize against the loanManager's ID
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        $loan->delete();
        return redirect()->route('loans.index')->with('status', 'Loan has been deleted successfully.');
    }

    private function recordLoanDisbursement(Loan $loan)
    {
        $loansReceivableAccount = Account::where('name', 'Loans Receivable')->first();
        $cashOnHandAccount = Account::where('name', 'Cash on Hand')->first();
        
        if ($loansReceivableAccount && $cashOnHandAccount) {
            // Debit Loans Receivable (Asset increase)
            GeneralLedgerTransaction::create([
                'account_id' => $loansReceivableAccount->id, 
                'loan_id' => $loan->id, 
                'transaction_date' => $loan->start_date, 
                'description' => 'Loan principal disbursed to ' . $loan->client->name, 
                'debit' => $loan->principal_amount, 
                'credit' => 0
            ]);
            
            // Credit Cash on Hand (Asset decrease)
            GeneralLedgerTransaction::create([
                'account_id' => $cashOnHandAccount->id, 
                'loan_id' => $loan->id, 
                'transaction_date' => $loan->start_date, 
                'description' => 'Loan principal disbursed to ' . $loan->client->name, 
                'debit' => 0, 
                'credit' => $loan->principal_amount
            ]);
            
            // The General Ledger logic must also account for the Processing Fee as Income/Revenue
            $processingFeeAccount = Account::where('name', 'Processing Fee Income')->first();
            if ($processingFeeAccount && $loan->processing_fee > 0) {
                // Debit Cash (Asset Increase) and Credit Income (Revenue Increase) for the fee
                GeneralLedgerTransaction::create([
                    'account_id' => $cashOnHandAccount->id, 
                    'loan_id' => $loan->id, 
                    'transaction_date' => $loan->start_date, 
                    'description' => 'Processing fee collected from ' . $loan->client->name, 
                    'debit' => $loan->processing_fee, 
                    'credit' => 0
                ]);
                GeneralLedgerTransaction::create([
                    'account_id' => $processingFeeAccount->id, 
                    'loan_id' => $loan->id, 
                    'transaction_date' => $loan->start_date, 
                    'description' => 'Processing fee collected from ' . $loan->client->name, 
                    'debit' => 0, 
                    'credit' => $loan->processing_fee
                ]);
            }
        }
    }

   public function downloadLoanAgreement(Loan $loan)
    {
        // Authorize against the loanManager's ID
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        
        $loan->load('client', 'guarantors', 'collaterals');
        
        // --- THE FIX ---
        // Get the entire LoanManager object. This has the business name and user details.
        $loanManager = Auth::user()->loanManager;
        // --- END FIX ---

        // Pass BOTH the $loan and the $loanManager to the PDF
        $pdf = Pdf::loadView('reports.pdf.loan-agreement', compact('loan', 'loanManager'));
        
        return $pdf->stream('loan-agreement-'.$loan->id.'.pdf');
    }
} 