<?php
namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\BankDeposit;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class BankDepositController extends Controller
{
    /**
     * This is the method that fixes the 404 error page.
     */
    public function index()
    {
        $deposits = Auth::user()->bankDeposits()->latest()->get();
        return view('loan-manager.banking.index', compact('deposits'));
    }

    /**
     * This is the method for saving a new deposit from the modal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
        ]);

        Auth::user()->bankDeposits()->create($validated);

        $cashOnHandAccount = Account::where('name', 'Cash on Hand')->firstOrFail();
        $cashAtBankAccount = Account::where('name', 'Cash at Bank')->firstOrFail();

        GeneralLedgerTransaction::create(['account_id' => $cashOnHandAccount->id, 'transaction_date' => $validated['deposit_date'], 'description' => 'Bank Deposit Ref: '.$validated['reference_number'], 'debit' => 0, 'credit' => $validated['amount']]);
        GeneralLedgerTransaction::create(['account_id' => $cashAtBankAccount->id, 'transaction_date' => $validated['deposit_date'], 'description' => 'Bank Deposit Ref: '.$validated['reference_number'], 'debit' => $validated['amount'], 'credit' => 0]);

        return redirect()->route('dashboard')->with('status', 'Bank deposit recorded successfully!');
    }
    
    /**
     * This method will be needed for the 'Print' button on the banking page.
     */
    public function downloadPdf()
    {
        $deposits = Auth::user()->bankDeposits()->latest()->get();
        $pdf = Pdf::loadView('reports.pdf.banking', compact('deposits'));
        return $pdf->stream('banking-report.pdf');
    }
}