<?php

namespace App\Http\Controllers\LoanManager;
use Barryvdh\DomPDF\Facade\Pdf;
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

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Auth::user()->loans()->with('client');

        if ($search = $request->input('search')) {
            $query->whereHas('client', function($subQuery) use ($search) {
                $searchTerm = strtolower($search);
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            });
        }
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
            'client_id' => ['required', Rule::exists('clients', 'id')->where('loan_manager_id', Auth::id())],
            'principal_amount' => 'required|numeric|min:0',
            'processing_fee' => 'nullable|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date',
            'guarantor_first_name' => 'nullable|string|max:255',
            'guarantor_last_name' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_phone_number' => 'required_with:guarantor_first_name|string|max:20',
            'guarantor_address' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_occupation' => 'nullable|string|max:255',
            'guarantor_relationship' => 'required_with:guarantor_first_name|string|max:100',
            'collateral_type' => 'nullable|string|max:100',
            'collateral_description' => 'required_with:collateral_type|string',
            'collateral_valuation_amount' => 'required_with:collateral_type|numeric|min:0',
        ]);

        DB::transaction(function () use ($validatedData, $request) {
            // Create the Loan first with all its details
            $loan = Loan::create([
                'client_id' => $validatedData['client_id'],
                'loan_manager_id' => Auth::id(),
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
                    'occupation' => $validatedData['guarantor_occupation'],
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

        return redirect()->route('loans.index')->with('status', 'New loan has been created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) { abort(403); }
        $loan->load('payments', 'guarantors', 'collaterals');

        // --- NEW DYNAMIC CALCULATION LOGIC ---
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
                default:        $dueDate->addMonths($i); break; // Monthly
            }
            $schedule[] = [ 'period' => $i, 'due_date' => $dueDate->toDateString(), 'payment_amount' => $paymentPerPeriod, 'principal' => $principalComponent, 'interest' => $interestComponent, 'balance' => ($i == $term) ? 0 : $balance ];
        }
        
        return view('loan-manager.loans.show', compact('loan', 'schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) { abort(403); }
        return view('loan-manager.loans.edit', compact('loan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) { abort(403); }
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
     * Remove the specified resource from storage.
     */
    public function destroy(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) { abort(403); }
        $loan->delete();
        return redirect()->route('loans.index')->with('status', 'Loan has been deleted successfully.');
    }

    /**
     * Generate a printable PDF loan agreement.
     */
    public function downloadLoanAgreement(Loan $loan)
    {
        if (Auth::id() !== $loan->loan_manager_id) {
            abort(403);
        }
        // Make sure we have all the relationships loaded
        $loan->load('client', 'guarantors');

        $pdf = Pdf::loadView('reports.pdf.loan-agreement', compact('loan'));
        return $pdf->stream('loan-agreement-'.$loan->id.'.pdf');
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