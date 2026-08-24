<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\SaccoReserveSetting;
use App\Models\StatutoryReserveTransfer;
use App\Services\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Statutory reserve fund: most SACCO/MFI regulators require a fixed
 * percentage of net surplus to be set aside into a non-distributable
 * reserve before any of it can be paid out as member dividends. This
 * controller computes that figure for a chosen period (reusing the exact
 * same net-surplus calculation the P&L report shows, via
 * ReportController::getProfitAndLossData()) and posts the transfer as a
 * journal entry: Dr Retained Earnings / Cr Statutory Reserve Fund.
 */
class StatutoryReserveController extends Controller
{
    public function editSettings()
    {
        $managerId = Auth::user()->loanManager->id;

        $settings = SaccoReserveSetting::firstOrCreate(
            ['loan_manager_id' => $managerId],
            ['statutory_reserve_percent' => 20.00]
        );

        return view('loan-manager.dividends.reserve-settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'statutory_reserve_percent' => 'required|numeric|min:0|max:100',
        ]);

        $settings = SaccoReserveSetting::firstOrCreate(['loan_manager_id' => $managerId]);
        $settings->update($validated);

        return back()->with('success', 'Statutory reserve policy updated.');
    }

    public function index(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $periodStart = $request->input('period_start', now()->startOfYear()->toDateString());
        $periodEnd = $request->input('period_end', now()->endOfYear()->toDateString());

        $reportController = new ReportController();
        $plData = $reportController->getProfitAndLossData(new Request([
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
        ]));
        $netSurplus = max(0, $plData['netProfit']);

        $settings = SaccoReserveSetting::where('loan_manager_id', $managerId)->first();
        $reservePercent = $settings->statutory_reserve_percent ?? 20.00;
        $requiredReserve = round($netSurplus * ($reservePercent / 100), 2);

        $reserveAccount = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '3200')->first();
        $currentReserveBalance = $reserveAccount ? $reserveAccount->balanceAsOf() : null;

        $alreadyTransferred = StatutoryReserveTransfer::where('loan_manager_id', $managerId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->sum('reserve_amount');

        $outstandingForPeriod = max(0, round($requiredReserve - $alreadyTransferred, 2));

        $transfers = StatutoryReserveTransfer::where('loan_manager_id', $managerId)
            ->with('journalEntry')
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('loan-manager.dividends.reserve-index', [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'netSurplus' => $netSurplus,
            'reservePercent' => $reservePercent,
            'requiredReserve' => $requiredReserve,
            'alreadyTransferred' => $alreadyTransferred,
            'outstandingForPeriod' => $outstandingForPeriod,
            'currentReserveBalance' => $currentReserveBalance,
            'transfers' => $transfers,
        ]);
    }

    public function transfer(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $reportController = new ReportController();
        $plData = $reportController->getProfitAndLossData(new Request([
            'start_date' => $validated['period_start'],
            'end_date' => $validated['period_end'],
        ]));
        $netSurplus = max(0, $plData['netProfit']);

        $settings = SaccoReserveSetting::where('loan_manager_id', $managerId)->first();
        $reservePercent = $settings->statutory_reserve_percent ?? 20.00;
        $requiredReserve = round($netSurplus * ($reservePercent / 100), 2);

        $alreadyTransferred = StatutoryReserveTransfer::where('loan_manager_id', $managerId)
            ->where('period_start', $validated['period_start'])
            ->where('period_end', $validated['period_end'])
            ->sum('reserve_amount');

        $amountToTransfer = round($requiredReserve - $alreadyTransferred, 2);

        if ($amountToTransfer <= 0.01) {
            return back()->with('error', 'Nothing to transfer — the reserve requirement for this period is already fully funded.');
        }

        $journalEntry = JournalPoster::post(
            $managerId,
            sprintf('Statutory reserve transfer (%s to %s)', $validated['period_start'], $validated['period_end']),
            'statutory_reserve',
            [
                ['code' => '3100', 'debit' => $amountToTransfer, 'description' => 'Retained earnings appropriated to reserve'],
                ['code' => '3200', 'credit' => $amountToTransfer, 'description' => 'Statutory reserve fund contribution'],
            ]
        );

        if (!$journalEntry) {
            return back()->with('error', 'Could not post the transfer — make sure Retained Earnings (3100) and Statutory Reserve Fund (3200) are active on your Chart of Accounts.');
        }

        StatutoryReserveTransfer::create([
            'loan_manager_id' => $managerId,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'net_surplus' => $netSurplus,
            'reserve_percent' => $reservePercent,
            'reserve_amount' => $amountToTransfer,
            'journal_entry_id' => $journalEntry->id,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', sprintf(
            'Transferred %s to the Statutory Reserve Fund (journal entry #%d posted).',
            number_format($amountToTransfer, 2),
            $journalEntry->id
        ));
    }
}
