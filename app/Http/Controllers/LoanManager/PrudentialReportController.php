<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\LoanClassificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

/**
 * Standard prudential/regulatory ratios for a microfinance/SACCO, computed
 * from the double-entry Chart of Accounts (the same ledger Trial Balance
 * and General Journal read from) plus the live loan classification data.
 * Deliberately ledger-based rather than re-deriving figures from raw
 * transaction tables the way the older hand-rolled Balance Sheet report
 * does — as more of the app's flows post through JournalPoster, the chart
 * of accounts becomes the more trustworthy source for these numbers.
 *
 * Ratios shown depend on account codes 1000/1010 (cash/bank), 1100 (loan
 * portfolio), 1150 (loan loss reserve), 2000/2100 (savings/fixed deposits),
 * and the standard asset/liability/equity account types — all part of the
 * standard chart seeded by ChartOfAccountController::seedDefaults(). If a
 * manager hasn't seeded/kept those accounts, the affected ratios show as
 * unavailable rather than a wrong number.
 */
class PrudentialReportController extends Controller
{
    public function index()
    {
        return view('loan-manager.reports.prudential-returns', $this->getData());
    }

    public function downloadPdf()
    {
        $data = $this->getData();
        $pdf = Pdf::loadView('reports.pdf.prudential-returns', $data);
        return $pdf->stream('prudential-returns-' . now()->toDateString() . '.pdf');
    }

    private function getData(): array
    {
        $managerId = Auth::user()->loanManager->id;

        $accounts = ChartOfAccount::where('loan_manager_id', $managerId)->get()->keyBy('code');

        $balance = function (string $code) use ($accounts) {
            return $accounts->has($code) ? $accounts[$code]->balanceAsOf() : null;
        };

        $cashOnHand = $balance('1000');
        $cashAtBank = $balance('1010');
        $loanPortfolio = $balance('1100');
        $loanLossReserve = $balance('1150'); // negative under asset convention (credit balance)
        $memberSavings = $balance('2000');
        $memberFixedDeposits = $balance('2100');

        $liquidAssets = ($cashOnHand !== null && $cashAtBank !== null) ? $cashOnHand + $cashAtBank : null;
        $totalDeposits = ($memberSavings !== null && $memberFixedDeposits !== null) ? $memberSavings + $memberFixedDeposits : null;
        $netLoanPortfolio = ($loanPortfolio !== null && $loanLossReserve !== null) ? $loanPortfolio + $loanLossReserve : $loanPortfolio;

        $totalAssets = $accounts->where('type', 'asset')->sum(fn ($a) => $a->balanceAsOf());
        $totalLiabilities = $accounts->where('type', 'liability')->sum(fn ($a) => $a->balanceAsOf());
        $totalEquity = $accounts->where('type', 'equity')->sum(fn ($a) => $a->balanceAsOf());

        $classification = LoanClassificationService::forManager($managerId);
        $rows = $classification['rows'];
        $totalOutstanding = $classification['summary']['total_outstanding'];
        $totalRequiredProvision = $classification['summary']['total_provision'];

        $par30Outstanding = 0.0;
        $par90Outstanding = 0.0;
        foreach ($rows as $row) {
            if ($row['days_late'] >= 31) {
                $par30Outstanding += $row['outstanding'];
            }
            if ($row['days_late'] >= 91) {
                $par90Outstanding += $row['outstanding'];
            }
        }

        $ratios = [
            'par_30' => $this->pct($par30Outstanding, $totalOutstanding),
            'par_90' => $this->pct($par90Outstanding, $totalOutstanding),
            'reserve_adequacy' => $loanLossReserve !== null
                ? $this->pct(abs(min(0, $loanLossReserve)), $totalRequiredProvision)
                : null,
            'npl_coverage' => $loanLossReserve !== null
                ? $this->pct(abs(min(0, $loanLossReserve)), $par90Outstanding)
                : null,
            'liquidity' => ($liquidAssets !== null && $totalDeposits !== null)
                ? $this->pct($liquidAssets, $totalDeposits)
                : null,
            'loans_to_deposits' => ($loanPortfolio !== null && $totalDeposits !== null)
                ? $this->pct($loanPortfolio, $totalDeposits)
                : null,
            'equity_to_assets' => $totalAssets > 0 ? $this->pct($totalEquity, $totalAssets) : null,
        ];

        return [
            'asOf' => now(),
            'accountsSeeded' => $accounts->isNotEmpty(),
            'cashOnHand' => $cashOnHand,
            'cashAtBank' => $cashAtBank,
            'liquidAssets' => $liquidAssets,
            'loanPortfolio' => $loanPortfolio,
            'loanLossReserve' => $loanLossReserve !== null ? abs(min(0, $loanLossReserve)) : null,
            'netLoanPortfolio' => $netLoanPortfolio,
            'memberSavings' => $memberSavings,
            'memberFixedDeposits' => $memberFixedDeposits,
            'totalDeposits' => $totalDeposits,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'totalOutstanding' => $totalOutstanding,
            'par30Outstanding' => $par30Outstanding,
            'par90Outstanding' => $par90Outstanding,
            'totalRequiredProvision' => $totalRequiredProvision,
            'ratios' => $ratios,
        ];
    }

    private function pct(float $numerator, ?float $denominator): ?float
    {
        if ($denominator === null || $denominator <= 0.009) {
            return null;
        }
        return round(($numerator / $denominator) * 100, 2);
    }
}
