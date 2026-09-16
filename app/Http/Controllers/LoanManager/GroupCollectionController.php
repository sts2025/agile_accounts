<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ClientGroup;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Group-lending workflow: a field officer visiting a joint-liability group
 * meeting needs (1) a printable sheet showing what each member owes today,
 * to physically collect against, and (2) a fast way to enter everyone's
 * payment afterwards without opening each loan individually. Both reuse
 * PaymentService::record() — the exact same accounting (journal posting,
 * compulsory savings, payoff detection) as the single-loan repayment
 * screen — so a group-collected payment is indistinguishable from one
 * entered any other way.
 */
class GroupCollectionController extends Controller
{
    public function collectionSheet($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $group = ClientGroup::where('loan_manager_id', $managerId)->findOrFail($id);

        $rows = $this->outstandingLoanRows($group);

        return view('loan-manager.client-groups.collection-sheet', [
            'group' => $group,
            'rows' => $rows,
        ]);
    }

    public function bulkPaymentForm($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $group = ClientGroup::where('loan_manager_id', $managerId)->findOrFail($id);

        $rows = $this->outstandingLoanRows($group);

        return view('loan-manager.client-groups.bulk-payment', [
            'group' => $group,
            'rows' => $rows,
        ]);
    }

    /**
     * Row-by-row, not all-or-nothing — same philosophy as the CSV client
     * importer: one officer typo on one member's line shouldn't cost the
     * rest of the group's payments that were entered correctly.
     */
    public function storeBulkPayment(Request $request, $id)
    {
        $manager = Auth::user()->loanManager;
        $managerId = $manager->id;
        $isMfi = (bool) $manager->is_mfi;
        $group = ClientGroup::where('loan_manager_id', $managerId)->findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payments' => 'required|array',
            'payments.*.loan_id' => 'required|integer',
            'payments.*.principal_paid' => 'nullable|numeric|min:0',
            'payments.*.interest_paid' => 'nullable|numeric|min:0',
            'payments.*.include' => 'nullable',
        ]);

        $recorded = 0;
        $skipped = [];

        foreach ($validated['payments'] as $row) {
            if (empty($row['include'])) {
                continue;
            }

            $principal = (float) ($row['principal_paid'] ?? 0);
            $interest = (float) ($row['interest_paid'] ?? 0);

            if ($principal + $interest <= 0) {
                continue;
            }

            try {
                PaymentService::record($managerId, $isMfi, [
                    'loan_id' => $row['loan_id'],
                    'principal_paid' => $principal,
                    'interest_paid' => $interest,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'reference_id' => null,
                    'notes' => 'Recorded via group collection — ' . $group->name,
                    'pay_from_savings' => false,
                ]);
                $recorded++;
            } catch (\Exception $e) {
                $skipped[] = ['loan_id' => $row['loan_id'], 'reason' => $e->getMessage()];
            }
        }

        $message = "{$recorded} payment(s) recorded for {$group->name}.";
        if (!empty($skipped)) {
            $message .= ' ' . count($skipped) . ' row(s) could not be recorded — check loan IDs: ' . collect($skipped)->pluck('loan_id')->implode(', ');
        }

        return redirect()->route('client-groups.show', $group->id)->with($skipped ? 'error' : 'success', $message);
    }

    /**
     * Outstanding (disbursed, not yet paid/written-off) loans for the
     * group, each with its next unpaid installment pulled from the real
     * repayment schedule (RepaymentScheduleGenerator) when one exists, plus
     * the pro-rata arrears estimate used everywhere else in the app as a
     * fallback for older loans that predate the schedule feature.
     */
    private function outstandingLoanRows(ClientGroup $group)
    {
        $loans = $group->loans()
            ->where('approval_status', 'disbursed')
            ->whereNotIn('status', ['paid', 'written_off'])
            ->with(['client', 'repaymentSchedules' => fn ($q) => $q->orderBy('installment_number')])
            ->get();

        return $loans->map(function ($loan) {
            $totalPaid = $loan->payments()->sum('amount_paid');
            $cumulativeDue = 0.0;
            $nextInstallment = null;

            foreach ($loan->repaymentSchedules as $installment) {
                $cumulativeDue += (float) $installment->amount;
                if ($totalPaid < $cumulativeDue - 0.01) {
                    $nextInstallment = $installment;
                    break;
                }
            }

            // Suggested principal/interest split for the pre-filled bulk
            // payment form. Reducing-balance loans have a real per-
            // installment breakdown (amortizationSchedule()) that declines
            // over time, so use the row matching the next due installment
            // when there is one; flat loans fall back to the same even
            // split the schedule generator itself uses.
            $term = max((int) $loan->term, 1);

            if (($loan->interest_method ?? 'flat') === 'reducing_balance') {
                $targetInstallment = $nextInstallment->installment_number ?? 1;
                $period = collect($loan->amortizationSchedule())->firstWhere('installment', $targetInstallment);
                $suggestedPrincipal = $period['principal'] ?? round($loan->principal_amount / $term, 2);
                $suggestedInterest = $period['interest'] ?? 0;
            } else {
                $suggestedPrincipal = round($loan->principal_amount / $term, 2);
                $suggestedInterest = round(($loan->principal_amount * $loan->interest_rate / 100) / $term, 2);
            }

            return (object) [
                'loan' => $loan,
                'client' => $loan->client,
                'next_due_date' => $nextInstallment?->due_date,
                'next_due_amount' => $nextInstallment?->amount,
                'arrears' => $loan->arrearsAmount(),
                'days_in_arrears' => $loan->daysInArrears(),
                'balance' => $loan->balance(),
                'suggested_principal' => $suggestedPrincipal,
                'suggested_interest' => $suggestedInterest,
            ];
        });
    }
}
