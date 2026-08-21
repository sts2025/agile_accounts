<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPenalty;
use App\Models\LoanPenaltySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanPenaltyController extends Controller
{
    /**
     * Add a penalty charge to a loan. Staff apply these manually (there's
     * no background job scanning for overdue loans yet); the tenant's
     * LoanPenaltySetting just pre-fills a sensible default amount on the
     * form so it's not typed from scratch every time.
     */
    public function store(Request $request, Loan $loan)
    {
        $managerId = Auth::user()->loanManager->id;

        if ($loan->loan_manager_id !== $managerId) { abort(403); }

        if (!in_array($loan->status, ['active', 'defaulted'], true)) {
            return back()->with('error', 'Penalties can only be added to an active or defaulted loan.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:1000',
        ]);

        LoanPenalty::create([
            'loan_id' => $loan->id,
            'loan_manager_id' => $managerId,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? null,
            'applied_by' => Auth::id(),
        ]);

        return back()->with('success', 'Penalty added.');
    }

    /**
     * Soft-remove a penalty (waive it) — the charge stays visible in the
     * loan's history, just flagged as removed so it stops counting toward
     * the balance due.
     */
    public function destroy(Request $request, Loan $loan, LoanPenalty $penalty)
    {
        $managerId = Auth::user()->loanManager->id;

        if ($loan->loan_manager_id !== $managerId || $penalty->loan_id !== $loan->id) { abort(403); }

        if ($penalty->is_removed) {
            return back()->with('error', 'This penalty has already been removed.');
        }

        $validated = $request->validate([
            'removal_reason' => 'nullable|string|max:1000',
        ]);

        $penalty->update([
            'is_removed' => true,
            'removed_by' => Auth::id(),
            'removed_at' => now(),
            'removal_reason' => $validated['removal_reason'] ?? null,
        ]);

        return back()->with('success', 'Penalty removed.');
    }
}
