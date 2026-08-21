<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class ErrorCorrectionController extends Controller
{
    /**
     * A single "did I make a mistake?" landing page, per the BKR spec's
     * dedicated Error Correction menu item. Doesn't introduce new
     * reversal mechanics of its own — it's a worklist over the
     * audit-safe correction paths that already exist elsewhere in the
     * app (PaymentController::update()/destroy(), JournalEntryController
     * ::reverse()), since duplicating that logic here would just create
     * two places that can drift out of sync.
     */
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        $recentPayments = Payment::whereHas('loan', function ($q) use ($managerId) {
            $q->where('loan_manager_id', $managerId);
        })->with('loan.client')->latest('payment_date')->latest('id')->limit(25)->get();

        $recentJournalEntries = JournalEntry::where('loan_manager_id', $managerId)
            ->where('is_reversed', false)
            ->latest('entry_date')->latest('id')->limit(25)->get();

        return view('loan-manager.accounting.error-correction.index', compact('recentPayments', 'recentJournalEntries'));
    }
}
