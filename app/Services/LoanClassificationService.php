<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPenaltySetting;

/**
 * Standard MFI/SACCO loan classification (aging-bucket labels) with
 * provisioning percentages. The five-tier label scheme and day boundaries
 * (Normal/Watch/Substandard/Doubtful/Loss, 0/31/91/181/361 days) mirror the
 * common regulatory convention used across most microfinance/SACCO
 * supervisory frameworks and are intentionally fixed, since the label a
 * loan carries in a prudential report needs to mean the same thing across
 * tenants.
 *
 * The provisioning *rate* applied within each tier is different — that's a
 * tenant-configurable financial policy (LoanPenaltySetting::provision_rates,
 * set on the Loan Penalty & Arrears Settings screen), because provisioning
 * requirements vary by jurisdiction/regulator. If a tenant hasn't configured
 * their own rates yet, this class's DEFAULT_RATES are used so the report is
 * still useful out of the box rather than showing 0% for every loan.
 */
class LoanClassificationService
{
    /**
     * Ordered ascending by min_days. tierFor() picks the last one whose
     * min_days the loan's days-late has reached.
     */
    public const TIERS = [
        ['min_days' => 0,   'label' => 'Normal'],
        ['min_days' => 31,  'label' => 'Watch'],
        ['min_days' => 91,  'label' => 'Substandard'],
        ['min_days' => 181, 'label' => 'Doubtful'],
        ['min_days' => 361, 'label' => 'Loss'],
    ];

    /**
     * Fallback provision rates (%) keyed the same way LoanPenaltySetting
     * stores them (bucket-start-day => rate), used only when a tenant has
     * not configured their own provision_rates.
     */
    public const DEFAULT_RATES = [
        '0' => 1,
        '31' => 5,
        '91' => 25,
        '181' => 50,
        '361' => 100,
    ];

    /**
     * Classify a single loan: how many days late it is, which regulatory
     * tier that falls into, the provisioning rate that applies, and the
     * resulting provision amount against its current outstanding balance
     * (principal + interest + fees + penalties still owed — the full
     * exposure, not just the amortising portion arrears is measured
     * against).
     */
    public static function classify(Loan $loan, ?LoanPenaltySetting $settings): array
    {
        $daysLate = $loan->daysInArrears();
        $tier = self::tierFor($daysLate);

        $hasCustomRates = $settings && !empty($settings->provision_rates);
        $rate = $hasCustomRates
            ? $settings->provisionRateForDaysLate($daysLate)
            : self::defaultRateForDaysLate($daysLate);

        $outstanding = $loan->balance();
        $provision = round($outstanding * ($rate / 100), 2);

        return [
            'loan' => $loan,
            'days_late' => $daysLate,
            'label' => $tier['label'],
            'provision_rate' => $rate,
            'outstanding' => $outstanding,
            'provision_amount' => $provision,
        ];
    }

    public static function tierFor(int $daysLate): array
    {
        $matched = self::TIERS[0];
        foreach (self::TIERS as $tier) {
            if ($daysLate >= $tier['min_days']) {
                $matched = $tier;
            }
        }
        return $matched;
    }

    private static function defaultRateForDaysLate(int $daysLate): float
    {
        $applicable = 0;
        foreach (self::DEFAULT_RATES as $bucketDays => $rate) {
            if ($daysLate >= (int) $bucketDays) {
                $applicable = max($applicable, (float) $rate);
            }
        }
        return $applicable;
    }

    /**
     * Classify every disbursed, still-outstanding loan for a tenant and
     * summarize by tier. Single source of truth shared by the Loan
     * Classification & Provisioning report and the Prudential Returns
     * report (PAR ratios), so both agree on the same underlying figures.
     *
     * @return array{rows: array, summary: array}
     */
    public static function forManager(int $managerId): array
    {
        $settings = LoanPenaltySetting::where('loan_manager_id', $managerId)->first();

        $loans = Loan::where('loan_manager_id', $managerId)
            ->where('approval_status', 'disbursed')
            ->whereNotIn('status', ['paid', 'written_off'])
            ->with(['client', 'payments'])
            ->get();

        $rows = [];
        foreach ($loans as $loan) {
            $classified = self::classify($loan, $settings);
            if ($classified['outstanding'] <= 0.01) {
                continue; // fully repaid balance, nothing to provision
            }
            $rows[] = $classified;
        }

        // Worst-first ordering (Loss down to Normal), then by outstanding desc.
        $tierOrder = array_reverse(array_column(self::TIERS, 'label'));
        usort($rows, function ($a, $b) use ($tierOrder) {
            $ai = array_search($a['label'], $tierOrder, true);
            $bi = array_search($b['label'], $tierOrder, true);
            if ($ai !== $bi) {
                return $ai <=> $bi;
            }
            return $b['outstanding'] <=> $a['outstanding'];
        });

        $byTier = [];
        foreach (self::TIERS as $tier) {
            $byTier[$tier['label']] = ['count' => 0, 'outstanding' => 0.0, 'provision' => 0.0];
        }

        $totalOutstanding = 0.0;
        $totalProvision = 0.0;

        foreach ($rows as $row) {
            $byTier[$row['label']]['count']++;
            $byTier[$row['label']]['outstanding'] += $row['outstanding'];
            $byTier[$row['label']]['provision'] += $row['provision_amount'];
            $totalOutstanding += $row['outstanding'];
            $totalProvision += $row['provision_amount'];
        }

        return [
            'rows' => $rows,
            'summary' => [
                'by_tier' => $byTier,
                'total_outstanding' => round($totalOutstanding, 2),
                'total_provision' => round($totalProvision, 2),
            ],
        ];
    }
}
