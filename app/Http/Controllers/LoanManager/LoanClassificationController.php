<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\LoanProvisioningRun;
use App\Services\JournalPoster;
use App\Services\LoanClassificationService;
use Illuminate\Support\Facades\Auth;

class LoanClassificationController extends Controller
{
    /**
     * Live classification report: every disbursed, still-outstanding loan
     * bucketed into its regulatory aging tier with its provisioning amount,
     * plus summary totals per tier and the most recent provisioning runs
     * for history/audit.
     */
    public function index()
    {
        $manager = Auth::user()->loanManager;
        $classification = LoanClassificationService::forManager($manager->id);
        $rows = $classification['rows'];
        $summary = $classification['summary'];

        $reserveAccount = ChartOfAccount::where('loan_manager_id', $manager->id)
            ->where('code', '1150')
            ->first();
        $currentReserve = $reserveAccount ? max(0, -$reserveAccount->balanceAsOf()) : null;

        $runs = LoanProvisioningRun::where('loan_manager_id', $manager->id)
            ->with('journalEntry')
            ->orderByDesc('run_date')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('loan-manager.reports.loan-classification', [
            'rows' => $rows,
            'summary' => $summary,
            'currentReserve' => $currentReserve,
            'runs' => $runs,
            'reserveAccountMissing' => !$reserveAccount,
        ]);
    }

    /**
     * Adjust the Loan Loss Reserve to match today's required provision
     * level and record a snapshot of the run. Posts only the *delta*
     * between what's required now and what the reserve currently holds —
     * running this repeatedly without the underlying portfolio changing
     * posts nothing (delta ~0), same as re-running seedDefaults().
     */
    public function run()
    {
        $manager = Auth::user()->loanManager;
        $managerId = $manager->id;

        $classification = LoanClassificationService::forManager($managerId);
        $rows = $classification['rows'];
        $summary = $classification['summary'];

        $reserveAccount = ChartOfAccount::where('loan_manager_id', $managerId)
            ->where('code', '1150')
            ->where('is_active', true)
            ->first();
        $expenseAccount = ChartOfAccount::where('loan_manager_id', $managerId)
            ->where('code', '5300')
            ->where('is_active', true)
            ->first();

        if (!$reserveAccount || !$expenseAccount) {
            return back()->with('error', 'Set up the standard chart of accounts first (Chart of Accounts → Seed Standard Accounts) — the Loan Loss Reserve (1150) and Loan Loss Provision Expense (5300) accounts are needed to run provisioning.');
        }

        $previousReserve = max(0, -$reserveAccount->balanceAsOf());
        $requiredReserve = $summary['total_provision'];
        $delta = round($requiredReserve - $previousReserve, 2);

        $journalEntry = null;

        if (abs($delta) > 0.01) {
            if ($delta > 0) {
                // Reserve needs topping up: Dr Provision Expense / Cr Reserve.
                $journalEntry = JournalPoster::post(
                    $managerId,
                    'Loan loss provisioning run — increase reserve to required level',
                    'loan_provisioning',
                    [
                        ['code' => '5300', 'debit' => $delta, 'description' => 'Provision expense for period'],
                        ['code' => '1150', 'credit' => $delta, 'description' => 'Increase loan loss reserve'],
                    ]
                );
            } else {
                // Portfolio improved: reserve is over-funded, write back the excess.
                $writeBack = abs($delta);
                $journalEntry = JournalPoster::post(
                    $managerId,
                    'Loan loss provisioning run — release excess reserve',
                    'loan_provisioning',
                    [
                        ['code' => '1150', 'debit' => $writeBack, 'description' => 'Release excess loan loss reserve'],
                        ['code' => '5300', 'credit' => $writeBack, 'description' => 'Provision expense write-back'],
                    ]
                );
            }
        }

        $run = LoanProvisioningRun::create([
            'loan_manager_id' => $managerId,
            'run_date' => now()->toDateString(),
            'loan_count' => count($rows),
            'total_outstanding' => $summary['total_outstanding'],
            'required_reserve' => $requiredReserve,
            'previous_reserve' => $previousReserve,
            'delta' => $delta,
            'breakdown' => $summary['by_tier'],
            'journal_entry_id' => $journalEntry?->id,
            'created_by' => Auth::id(),
        ]);

        if (abs($delta) <= 0.01) {
            return back()->with('success', 'Provisioning run recorded — reserve already at the required level (no journal entry needed).');
        }

        if (!$journalEntry) {
            return back()->with('error', 'Run recorded, but the adjusting journal entry could not be posted — check that accounts 1150 and 5300 are active.');
        }

        return back()->with('success', sprintf(
            'Provisioning run complete. Reserve %s by %s (journal entry #%d posted).',
            $delta > 0 ? 'increased' : 'decreased',
            number_format(abs($delta), 2),
            $journalEntry->id
        ));
    }
}
