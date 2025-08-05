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

        $transfer = Auth::user()->cashTransfers()->create($validated);

        // Accounting Entries
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

    public function index()
    {
        $transfers = Auth::user()->cashTransfers()->latest()->get();
        return view('loan-manager.cash-transfers.index', compact('transfers'));
    }

    /**
     * Generate a PDF of all cash transfers.
     */
    public function downloadPdf()
    {
        $transfers = Auth::user()->cashTransfers()->latest()->get();
        $pdf = Pdf::loadView('reports.pdf.cash-transfers', compact('transfers'));
        return $pdf->stream('cash-transfers-report.pdf');
    }
}