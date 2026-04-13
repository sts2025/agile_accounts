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

// --- NEW IMPORTS FOR LARAVEL 11/12 MIDDLEWARE ---
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     * This replaces the old $this->middleware() in the constructor.
     */
    public static function middleware(): array
    {
        return [
            // Ensure only authorized users can update or delete records
            new Middleware('elevated', only: ['update', 'destroy']),
        ];
    }

    /**
     * Display the list of loans for the manager.
     */
    public function index(Request $request)
    {
        $loanManager = Auth::user()->loanManager;
        
        $query = $loanManager->loans()->with(['client', 'payments']);

        // Search by Client Name
        if ($search = $request->input('search')) {
            $query->whereHas('client', function($subQuery) use ($search) {
                $searchTerm = strtolower($search);
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        // Sidebar Status Filters
        if ($filter = $request->input('filter')) {
            if ($filter === 'completed') {
                $query->where('status', 'paid');
            } elseif ($filter === 'active') {
                $query->where('status', 'active');
            } elseif ($filter === 'defaulted') {
                $query->where('status', 'defaulted');
            }
        }
        
        $currency_symbol = $loanManager->currency_symbol ?? 'UGX'; 
        $loans = $query->latest()->paginate(10); 
        
        return view('loan-manager.loans.index', compact('loans', 'currency_symbol'));
    }

    /**
     * Show create loan form.
     */
    public function create()
    {
        $clients = Auth::user()->loanManager->clients()
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('loan-manager.loans.create', compact('clients'));
    }

    /**
     * Store a new loan record.
     */
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
            'start_date' => 'required|date', 

            // Guarantor validation
            'guarantor_first_name' => 'nullable|string|max:255',
            'guarantor_last_name' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_phone_number' => 'required_with:guarantor_first_name|string|max:20',
            'guarantor_address' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_occupation' => 'nullable|string|max:255',
            'guarantor_relationship' => 'required_with:guarantor_first_name|string|max:100',

            // Collateral validation
            'collateral_type' => 'nullable|string|max:100',
            'collateral_description' => 'required_with:collateral_type|string',
            'collateral_valuation_amount' => 'required_with:collateral_type|numeric|min:1', 
        ]);

        DB::transaction(function () use ($validatedData, $request, $loanManagerId) {
            $loanCount = Loan::where('loan_manager_id', $loanManagerId)->count();
            
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
                'reference_id' => 'LN-' . str_pad($loanCount + 1, 4, '0', STR_PAD_LEFT),
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

    /**
     * Update loan status via AJAX.
     */
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

    /**
     * Show loan repayment calculator.
     */
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

    /**
     * Show loan details.
     */
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

    /**
     * Show edit loan form.
     */
    public function edit(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        return view('loan-manager.loans.edit', compact('loan'));
    }

    /**
     * Update loan record.
     */
    public function update(Request $request, Loan $loan)
    {
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

    /**
     * Delete loan record.
     */
    public function destroy(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        $loan->delete();
        return redirect()->route('loans.index')->with('status', 'Loan has been deleted successfully.');
    }

    /**
     * Record accounting entries for loan disbursement.
     */
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

    /**
     * Generate printable Loan Agreement.
     * Matches Route name 'loans.downloadAgreement'
     */
    public function downloadLoanAgreement($id)
    {
        $loan = Loan::with(['client', 'loanManager', 'guarantors', 'collaterals'])->findOrFail($id);
        
        // Security check
        if ($loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        // Return the printable layout view
        return view('loan-manager.loans.agreement-pdf', compact('loan'));
    }
}