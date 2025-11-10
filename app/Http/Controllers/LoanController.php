<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class LoanController extends Controller
{
    /**
     * Show Loan Calculator page.
     */
    public function showCalculator(Request $request)
    {
        $principal = $request->input('principal_amount', 0);
        $interestRate = $request->input('interest_rate', 0);
        $term = $request->input('term', 0);
        $frequency = $request->input('repayment_frequency', 'Monthly');
        $calculationPerformed = $request->has('calculate');

        $schedule = [];

        if ($calculationPerformed && $principal > 0 && $term > 0 && $interestRate > 0) {
            // Determine number of periods per year
            $periodsPerYear = match ($frequency) {
                'Daily' => 365,
                'Weekly' => 52,
                'Monthly' => 12,
                default => 12,
            };

            // Interest rate per period
            $r = ($interestRate / 100) / $periodsPerYear;

            // Monthly/periodic payment (Amortized formula)
            $payment = $r > 0
                ? ($principal * $r) / (1 - pow(1 + $r, -$term))
                : ($principal / $term);

            // Build repayment schedule
            $balance = $principal;
            $today = Carbon::today();

            for ($i = 1; $i <= $term; $i++) {
                $interest = $balance * $r;
                $principalPayment = $payment - $interest;
                $balance -= $principalPayment;

                // Adjust due date based on frequency
                $dueDate = match ($frequency) {
                    'Daily' => $today->copy()->addDays($i),
                    'Weekly' => $today->copy()->addWeeks($i),
                    'Monthly' => $today->copy()->addMonths($i),
                    default => $today->copy()->addMonths($i),
                };

                $schedule[] = [
                    'period' => $i,
                    'due_date' => $dueDate,
                    'payment_amount' => round($payment),
                    'principal' => round($principalPayment),
                    'interest' => round($interest),
                    'balance' => max(round($balance), 0),
                ];
            }
        }

        return view('loan-manager.loans.calculator', [
            'principal' => $principal,
            'interestRate' => $interestRate,
            'term' => $term,
            'frequency' => $frequency,
            'calculationPerformed' => $calculationPerformed,
            'schedule' => $schedule,
        ]);
    }
}
