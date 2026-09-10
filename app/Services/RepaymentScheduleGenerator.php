<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Carbon\Carbon;

/**
 * Populates the (previously unused) repayment_schedules table with a real
 * per-installment due-date schedule, split evenly across the loan's term —
 * same flat-rate, even-amortisation assumption Loan::scheduledRepayable()
 * and arrearsAmount() already use elsewhere, so this schedule agrees with
 * the aging/classification figures rather than introducing a second,
 * different notion of "how much should be paid by when".
 *
 * Idempotent: regenerating (e.g. after a reschedule) simply replaces
 * whatever rows already existed for the loan. Nothing else in the app
 * references repayment_schedules.id, so this is safe to do wholesale
 * rather than trying to diff and patch individual rows.
 */
class RepaymentScheduleGenerator
{
    public static function generate(Loan $loan): void
    {
        $term = max((int) $loan->term, 1);
        $totalRepayable = $loan->scheduledRepayable();

        if ($totalRepayable <= 0 || !$loan->start_date) {
            return;
        }

        $loan->repaymentSchedules()->delete();

        $baseInstallment = round($totalRepayable / $term, 2);
        $startDate = Carbon::parse($loan->start_date);
        $frequency = strtolower($loan->repayment_frequency ?? 'monthly');

        $runningTotal = 0.0;
        $rows = [];

        for ($i = 1; $i <= $term; $i++) {
            $dueDate = self::dueDateFor($startDate, $frequency, $i);

            // Last installment absorbs any rounding remainder so the
            // schedule's total always ties out exactly to scheduledRepayable().
            $amount = $i === $term
                ? round($totalRepayable - $runningTotal, 2)
                : $baseInstallment;

            $runningTotal += $amount;

            $rows[] = [
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $dueDate->toDateString(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        RepaymentSchedule::insert($rows);
    }

    private static function dueDateFor(Carbon $startDate, string $frequency, int $installmentNumber): Carbon
    {
        $date = $startDate->copy();

        return match (true) {
            str_contains($frequency, 'day') => $date->addDays($installmentNumber),
            str_contains($frequency, 'week') => $date->addWeeks($installmentNumber),
            default => $date->addMonths($installmentNumber),
        };
    }
}
