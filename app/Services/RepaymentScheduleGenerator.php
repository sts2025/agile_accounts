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

        $startDate = Carbon::parse($loan->start_date);
        $frequency = strtolower($loan->repayment_frequency ?? 'monthly');
        $isReducingBalance = ($loan->interest_method ?? 'flat') === 'reducing_balance';

        // Processing fee is spread evenly across every installment either
        // way — it isn't part of the amortization math, just added on top.
        $feePerInstallment = round(($loan->processing_fee ?? 0) / $term, 2);

        $rows = [];
        $runningTotal = 0.0;

        if ($isReducingBalance) {
            // Declining installment amount: equal principal per period,
            // interest only on what's still outstanding, so later
            // installments are smaller than earlier ones.
            foreach ($loan->amortizationSchedule() as $period) {
                $dueDate = self::dueDateFor($startDate, $frequency, $period['installment']);
                $amount = round($period['principal'] + $period['interest'] + $feePerInstallment, 2);
                $runningTotal += $amount;

                $rows[] = [
                    'loan_id' => $loan->id,
                    'installment_number' => $period['installment'],
                    'amount' => $amount,
                    'due_date' => $dueDate->toDateString(),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Rounding remainder (if any) is absorbed into the last row so
            // the schedule always ties out exactly to scheduledRepayable().
            if (!empty($rows)) {
                $lastIndex = array_key_last($rows);
                $rows[$lastIndex]['amount'] = round($rows[$lastIndex]['amount'] + ($totalRepayable - $runningTotal), 2);
            }
        } else {
            // Flat-rate: even split across every installment, same as before.
            $baseInstallment = round($totalRepayable / $term, 2);

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
