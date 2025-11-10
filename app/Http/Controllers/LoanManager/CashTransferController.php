<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\CashTransfer;
use App\Models\Account;
use App\Models\GeneralLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class CashTransferController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // CORRECTED: Create the transfer via the loanManager
        $transfer = Auth::user()->loanManager->cashTransfers()->create($validated);

        // Your existing accounting logic is preserved
        $cashAccount = Account::where('name', 'Cash on Hand')->firstOrFail();
        $transferAccount = Account::where('name', 'Inter-branch Transfers')->firstOrFail();

        if ($transfer->type === 'in') { // Receivable
            GeneralLedgerTransaction::create(['account_id' => $cashAccount->id, 'transaction_date' => $transfer->transaction_date, 'description' => $transfer->description, 'debit' => $transfer->amount, 'credit' => 0]);
            GeneralLedgerTransaction::create(['account_id' => $transferAccount->id, 'transaction_date' => $transfer->transaction_date, 'description' => $transfer->description, 'debit' => 0, 'credit' => $transfer->amount]);
        } else { // Payable
            GeneralLedgerTransaction::create(['account_id' => $cashAccount->id, 'transaction_date' => $transfer->transaction_date, 'description' => $transfer->description, 'debit' => 0, 'credit' => $transfer->amount]);
            GeneralLedgerTransaction::create(['account_id' => $transferAccount->id, 'transaction_date' => $transfer->transaction_date, 'description' => $transfer->description, 'debit' => $transfer->amount, 'credit' => 0]);
        }

        return redirect()->route('dashboard')->with('status', 'Cash transfer recorded successfully!');
    }

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // CORRECTED: Fetch transfers from the loanManager
        $transfers = Auth::user()->loanManager->cashTransfers()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->latest()
            ->get();
            
        return view('loan-manager.transactions.cash-transactions.index', compact('transfers', 'startDate', 'endDate'));
    }

    public function downloadPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // CORRECTED: Fetch transfers from the loanManager for the PDF
        $transfers = Auth::user()->loanManager->cashTransfers()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->latest()
            ->get();

        $pdf = Pdf::loadView('reports.pdf.cash-transfers', compact('transfers', 'startDate', 'endDate'));
        return $pdf->stream('cash-transfers-report.pdf');
    }
}