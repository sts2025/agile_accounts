<?php

namespace App\Http\Controllers\LoanManager;

// FIX: Extend the Framework Controller directly to ensure middleware() exists
use Illuminate\Routing\Controller; 
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
    /**
     * Constructor to apply elevated privileges middleware.
     */
    public function __construct()
    {
        // This will now work because we are extending Illuminate\Routing\Controller
        $this->middleware('elevated')->only(['update', 'destroy']);
    }

    public function index(Request $request)
    {
        $loanManager = Auth::user()->loanManager;
        
        // Start the query relative to the logged-in manager
        $query = $loanManager->loans()->with('client');

        // --- 1. Search Logic ---
        if ($search = $request->input('search')) {
            $query->whereHas('client', function($subQuery) use ($search) {
                $searchTerm = strtolower($search);
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        // --- 2. Sidebar Filter Logic (NEW) ---
        // This handles the "Completed Loans" link from your sidebar
        if ($filter = $request->input('filter')) {
            if ($filter === 'completed') {
                // Assumes 'paid' is the status used for completed loans in your DB
                $query->where('status', 'paid');
            }
        }
        
        $currency_symbol = $loanManager->currency_symbol ?? 'UGX'; 

        $loans = $query->latest()->get(); 
        
        return view('loan-manager.loans.index', compact('loans', 'currency_symbol'));
    }

    public function create()
    {
        $clients = Auth::user()->loanManager->clients;
        return view('loan-manager.loans.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $loanManagerId = Auth::user()->loanManager->id;

        $validatedData = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('loan_manager_id', $loanManagerId)],
            'principal_amount' => 'required|numeric|min:100', 
            'processing_fee' => 'nullable|numeric|min:0', 
            'interest_rate' => 'required|numeric|min:0|max:100', 
            'term' => 'required|integer|min:1', 
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date|after_or_equal:today', 

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
            'collateral_valuation_amount' => 'required_with:collateral_type|numeric|min:1', 
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

            if ($request->filled('collateral_type')) {
                $loan->collaterals()->create([
                    'collateral_type' => $validatedData['collateral_type'],
                    'description' => $validatedData['collateral_description'],
                    'valuation_amount' => $validatedData['collateral_valuation_amount'],
                ]);
            }
            
            $this->recordLoanDisbursement($loan);
        });

        return redirect()->route('loans.index')->with('success', 'New loan created and recorded successfully!');
    }

    public function updateStatus(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { 
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'new_status' => 'required|string|in:active,paid,defaulted',
        ]);

        $loan->status = $validated['new_status'];
        $loan->save();

        return response()->json([
            'success' => true,
            'message' => 'Loan status updated successfully.',
            'status' => $loan->status,
            'loan_id' => $loan->id
        ]);
    }

    public function showCalculator(Request $request)
    {
        $schedule = [];
        $calculationPerformed = false;
        $totalInterest = 0; 
        $totalRepayable = 0;
        
        $principal = $request->input('principal_amount', 1000000);
        $interestRate = $request->input('interest_rate', 10);
        $term = $request->input('term', 12);
        $frequency = $request->input('repayment_frequency', 'Monthly');

        if ($request->has('calculate')) {
            $calculationPerformed = true;
            
            if ($principal > 0 && $term > 0) {
                $totalInterest = $principal * ($interestRate / 100);
                $totalRepayable = $principal + $totalInterest;
                
                $paymentPerPeriod = $totalRepayable / $term;
                $principalComponent = $principal / $term;
                $interestComponent = $totalInterest / $term;
                
                $balance = $totalRepayable;
                $startDate = Carbon::today();

                for ($i = 1; $i <= $term; $i++) {
                    $balance -= $paymentPerPeriod;
                    $dueDate = $startDate->copy();
                    
                    switch ($frequency) {
                        case 'Daily':  $dueDate->addDays($i); break;
                        case 'Weekly': $dueDate->addWeeks($i); break;
                        default: $dueDate->addMonths($i); break;
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

        return view('loan-manager.loans.calculator', compact( 
            'schedule', 'principal', 'interestRate', 'term', 'frequency', 
            'calculationPerformed', 'totalRepayable', 'totalInterest'
        ));
    }

    public function show(Loan $loan)
    {
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
                case 'Daily':  $dueDate->addDays($i); break;
                case 'Weekly': $dueDate->addWeeks($i); break;
                default: $dueDate->addMonths($i); break;
            }
            $schedule[] = [ 'period' => $i, 'due_date' => $dueDate->toDateString(), 'payment_amount' => $paymentPerPeriod, 'principal' => $principalComponent, 'interest' => $interestComponent, 'balance' => ($i == $term) ? 0 : $balance ];
        }
        
        return view('loan-manager.loans.show', compact('loan', 'schedule'));
    }

    public function edit(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        return view('loan-manager.loans.edit', compact('loan'));
    }

    public function update(Request $request, Loan $loan)
    {
        // Protected by 'elevated' middleware
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
        // Protected by 'elevated' middleware
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        $loan->delete();
        return redirect()->route('loans.index')->with('status', 'Loan has been deleted successfully.');
    }

    private function recordLoanDisbursement(Loan $loan)
    {
        $loansReceivableAccount = Account::where('name', 'Loans Receivable')->first();
        $cashOnHandAccount = Account::where('name', 'Cash on Hand')->first();
        
        if ($loansReceivableAccount && $cashOnHandAccount) {
            GeneralLedgerTransaction::create([
                'account_id' => $loansReceivableAccount->id, 
                'loan_id' => $loan->id, 
                'transaction_date' => $loan->start_date, 
                'description' => 'Loan principal disbursed to ' . $loan->client->name, 
                'debit' => $loan->principal_amount, 
                'credit' => 0
            ]);
            
            GeneralLedgerTransaction::create([
                'account_id' => $cashOnHandAccount->id, 
                'loan_id' => $loan->id, 
                'transaction_date' => $loan->start_date, 
                'description' => 'Loan principal disbursed to ' . $loan->client->name, 
                'debit' => 0, 
                'credit' => $loan->principal_amount
            ]);
            
            $processingFeeAccount = Account::where('name', 'Processing Fee Income')->first();
            if ($processingFeeAccount && $loan->processing_fee > 0) {
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
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        
        $loan->load('client', 'guarantors', 'collaterals');
        $loanManager = Auth::user()->loanManager;

        $pdf = Pdf::loadView('reports.pdf.loan-agreement', compact('loan', 'loanManager'));
        
        return $pdf->stream('loan-agreement-'.$loan->id.'.pdf');
    }
}