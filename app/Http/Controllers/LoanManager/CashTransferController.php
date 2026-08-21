<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\CashTransfer;
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
        Auth::user()->loanManager->cashTransfers()->create($validated);

        // Note: this used to also post to the formal GeneralLedgerTransaction/
        // Account tables via Account::firstOrFail() — a global (non-tenant-
        // scoped), unread ledger that no report anywhere consumes. Removed:
        // it was dead weight, and firstOrFail() meant this whole action would
        // crash with a 404 for any tenant if those global seed rows were ever
        // missing. cash_transfers is the real, read source of truth already.

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