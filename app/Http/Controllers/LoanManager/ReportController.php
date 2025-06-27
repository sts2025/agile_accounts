<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    // --- TRIAL BALANCE METHODS ---

    public function trialBalance()
    {
        $managerId = Auth::id();
        $data = $this->getTrialBalanceData($managerId);

        $message = "*Trial Balance Summary*\nAs of " . now()->format('d-M-Y') . "\n\n";
        $message .= "Total Debits: *UGX " . number_format($data['grandTotalDebits'], 0) . "*\n";
        $message .= "Total Credits: *UGX " . number_format($data['grandTotalCredits'], 0) . "*\n\n";
        $message .= "Status: Your ledger is balanced.";
        $data['whatsappMessage'] = urlencode($message);

        return view('loan-manager.reports.trial-balance', $data);
    }

    public function downloadTrialBalance()
    {
        $managerId = Auth::id();
        $data = $this->getTrialBalanceData($managerId);
        $pdf = Pdf::loadView('reports.pdf.trial-balance', $data);
        return $pdf->stream('trial-balance-'.now()->format('Y-m-d').'.pdf');
    }

    private function getTrialBalanceData($managerId)
    {
        $accounts = Account::whereHas('generalLedgerTransactions.loan', fn($q) => $q->where('loan_manager_id', $managerId))
            ->withSum(['generalLedgerTransactions as total_debits' => fn($q) => $q->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'debit')
            ->withSum(['generalLedgerTransactions as total_credits' => fn($q) => $q->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'credit')
            ->get();
        
        return [
            'accounts' => $accounts,
            'grandTotalDebits' => $accounts->sum('total_debits'),
            'grandTotalCredits' => $accounts->sum('total_credits'),
        ];
    }

    // --- PROFIT & LOSS METHODS ---

    public function profitAndLoss(Request $request)
    {
        $data = $this->getProfitAndLossData($request);
        $message = "*P&L Summary*\nPeriod: " . Carbon::parse($data['startDate'])->format('d-M-Y') . " to " . Carbon::parse($data['endDate'])->format('d-M-Y') . "\n\nNet Profit: *UGX " . number_format($data['netProfit'], 0) . "*";
        $data['whatsappMessage'] = urlencode($message);
        return view('loan-manager.reports.profit-and-loss', $data);
    }

    public function downloadProfitAndLoss(Request $request)
    {
        $data = $this->getProfitAndLossData($request);
        $pdf = Pdf::loadView('reports.pdf.profit-and-loss', $data);
        return $pdf->stream('profit-and-loss-'.now()->format('Y-m-d').'.pdf');
    }

    private function getProfitAndLossData(Request $request)
    {
        $managerId = Auth::id();
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfMonth();

        $incomeAccounts = Account::where('type', 'Income')->withSum(['generalLedgerTransactions as period_total' => fn($q) => $q->whereBetween('transaction_date', [$startDate, $endDate])->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'credit')->get();
        $expenseAccounts = Account::where('type', 'Expense')->withSum(['generalLedgerTransactions as period_total' => fn($q) => $q->whereBetween('transaction_date', [$startDate, $endDate])->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'debit')->get();
        
        $totalIncome = $incomeAccounts->sum('period_total');
        $totalExpenses = $expenseAccounts->sum('period_total');

        return ['incomeAccounts' => $incomeAccounts, 'expenseAccounts' => $expenseAccounts, 'totalIncome' => $totalIncome, 'totalExpenses' => $totalExpenses, 'netProfit' => $totalIncome - $totalExpenses, 'startDate' => $startDate->toDateString(), 'endDate' => $endDate->toDateString()];
    }

    // --- BALANCE SHEET METHODS ---

    public function balanceSheet(Request $request)
    {
        $data = $this->getBalanceSheetData($request);
        $message = "*Balance Sheet Summary*\nAs of " . Carbon::parse($data['asOfDate'])->format('d-M-Y') . "\n\nTotal Assets: *UGX " . number_format($data['totalAssets'], 0) . "*";
        $data['whatsappMessage'] = urlencode($message);
        return view('loan-manager.reports.balance-sheet', $data);
    }

    public function downloadBalanceSheet(Request $request)
    {
        $data = $this->getBalanceSheetData($request);
        $pdf = Pdf::loadView('reports.pdf.balance-sheet', $data);
        return $pdf->stream('balance-sheet-'.now()->format('Y-m-d').'.pdf');
    }

    // In app/Http/Controllers/LoanManager/ReportController.php

    private function getBalanceSheetData(Request $request)
    {
        $managerId = Auth::id();
        $asOfDate = $request->input('as_of_date') ? Carbon::parse($request->input('as_of_date')) : Carbon::today();

        // Calculate scoped Net Income (this part is the same)
        $totalIncome = Account::where('type', 'Income')->first()?->generalLedgerTransactions()->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->where('transaction_date', '<=', $asOfDate)->sum('credit') ?? 0;
        $totalExpenses = Account::where('type', 'Expense')->first()?->generalLedgerTransactions()->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->where('transaction_date', '<=', $asOfDate)->sum('debit') ?? 0;
        $netIncome = $totalIncome - $totalExpenses;

        // Function to build the scoped query (this part is the same)
        $accountQuery = function ($type) use ($asOfDate, $managerId) {
            return Account::where('type', $type)->whereHas('generalLedgerTransactions.loan', fn($q) => $q->where('loan_manager_id', $managerId))
                ->withSum(['generalLedgerTransactions as debit_total' => fn($q) => $q->where('transaction_date', '<=', $asOfDate)->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'debit')
                ->withSum(['generalLedgerTransactions as credit_total' => fn($q) => $q->where('transaction_date', '<=', $asOfDate)->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'credit')
                ->get();
        };
        
        // --- THIS IS THE CORRECTED LOGIC ---

        // Fetch Assets and calculate balance for each
        $assets = $accountQuery('Asset');
        foreach ($assets as $account) {
            $account->balance = $account->debit_total - $account->credit_total;
        }

        // Fetch Liabilities and calculate balance for each
        $liabilities = $accountQuery('Liability');
        foreach ($liabilities as $account) {
            $account->balance = $account->credit_total - $account->debit_total;
        }
        
        // Fetch Equity and calculate balance for each
        $equityAccounts = $accountQuery('Equity');
        foreach ($equityAccounts as $account) {
            $account->balance = $account->credit_total - $account->debit_total;
        }
        
        // --- END OF CORRECTED LOGIC ---


        // Calculate totals (this part is the same)
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equityAccounts->sum('balance') + $netIncome;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return compact('assets', 'liabilities', 'equityAccounts', 'netIncome', 'totalAssets', 'totalLiabilities', 'totalEquity', 'totalLiabilitiesAndEquity', 'asOfDate');
    }
}