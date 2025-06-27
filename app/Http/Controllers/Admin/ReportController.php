<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Client;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display a trial balance report for the entire system.
     */
    public function trialBalance()
    {
        $accounts = Account::withSum('generalLedgerTransactions as total_debits', 'debit')
                            ->withSum('generalLedgerTransactions as total_credits', 'credit')
                            ->get();

        $grandTotalDebits = $accounts->sum('total_debits');
        // THIS IS THE CORRECTED LINE FOR THE TYPO
        $grandTotalCredits = $accounts->sum('total_credits');

        return view('admin.reports.trial-balance', [
            'accounts' => $accounts,
            'grandTotalDebits' => $grandTotalDebits,
            'grandTotalCredits' => $grandTotalCredits,
        ]);
    }

    /**
     * Display a profit and loss statement for the entire system.
     */
    public function profitAndLoss(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfMonth();

        $incomeAccounts = Account::where('type', 'Income')->withSum(['generalLedgerTransactions as period_total' => fn($q) => $q->whereBetween('transaction_date', [$startDate, $endDate])], 'credit')->get();
        $expenseAccounts = Account::where('type', 'Expense')->withSum(['generalLedgerTransactions as period_total' => fn($q) => $q->whereBetween('transaction_date', [$startDate, $endDate])], 'debit')->get();
        
        $totalIncome = $incomeAccounts->sum('period_total');
        $totalExpenses = $expenseAccounts->sum('period_total');
        $netProfit = $totalIncome - $totalExpenses;

        return view('admin.reports.profit-and-loss', [
            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ]);
    }

    /**
     * Display a balance sheet statement for the entire system.
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date') ? Carbon::parse($request->input('as_of_date')) : Carbon::today();

        $totalIncome = Account::where('type', 'Income')->first()?->generalLedgerTransactions()->where('transaction_date', '<=', $asOfDate)->sum('credit') ?? 0;
        $totalExpenses = Account::where('type', 'Expense')->first()?->generalLedgerTransactions()->where('transaction_date', '<=', $asOfDate)->sum('debit') ?? 0;
        $netIncome = $totalIncome - $totalExpenses;

        $accountQuery = function ($type) use ($asOfDate) {
            return Account::where('type', $type)
                ->withSum(['generalLedgerTransactions as debit_total' => fn($q) => $q->where('transaction_date', '<=', $asOfDate)], 'debit')
                ->withSum(['generalLedgerTransactions as credit_total' => fn($q) => $q->where('transaction_date', '<=', $asOfDate)], 'credit')
                ->get();
        };

        // --- THIS IS THE CORRECTED CALCULATION LOGIC ---
        $assets = $accountQuery('Asset');
        foreach ($assets as $account) {
            $account->balance = $account->debit_total - $account->credit_total;
        }

        $liabilities = $accountQuery('Liability');
        foreach ($liabilities as $account) {
            $account->balance = $account->credit_total - $account->debit_total;
        }
        
        $equityAccounts = $accountQuery('Equity');
        foreach ($equityAccounts as $account) {
            $account->balance = $account->credit_total - $account->debit_total;
        }
        // --- END OF CORRECTED LOGIC ---
        
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equityAccounts->sum('balance') + $netIncome;

        return view('admin.reports.balance-sheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equityAccounts' => $equityAccounts,
            'netIncome' => $netIncome,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'totalLiabilitiesAndEquity' => $totalLiabilities + $totalEquity,
            'asOfDate' => $asOfDate,
        ]);
    }
}