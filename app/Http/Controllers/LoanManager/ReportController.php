<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    // --- TRIAL BALANCE METHODS ---
    public function trialBalance()
    {
        $data = $this->getTrialBalanceData(Auth::id());
        $message = "*Trial Balance Summary*\nAs of " . now()->format('d-M-Y') . "\n\nTotal Debits: *UGX " . number_format($data['grandTotalDebits'], 0) . "*\nTotal Credits: *UGX " . number_format($data['grandTotalCredits'], 0) . "*\n\nStatus: Your ledger is balanced.";
        $data['whatsappMessage'] = urlencode($message);
        return view('loan-manager.reports.trial-balance', $data);
    }
    public function downloadTrialBalance()
    {
        $data = $this->getTrialBalanceData(Auth::id());
        $pdf = Pdf::loadView('reports.pdf.trial-balance', $data);
        return $pdf->stream('trial-balance-'.now()->format('Y-m-d').'.pdf');
    }
    private function getTrialBalanceData($managerId)
    {
        $accounts = Account::whereHas('generalLedgerTransactions.loan', fn($q) => $q->where('loan_manager_id', $managerId))
            ->withSum(['generalLedgerTransactions as total_debits' => fn($q) => $q->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'debit')
            ->withSum(['generalLedgerTransactions as total_credits' => fn($q) => $q->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'credit')
            ->get();
        return ['accounts' => $accounts, 'grandTotalDebits' => $accounts->sum('total_debits'), 'grandTotalCredits' => $accounts->sum('total_credits')];
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
    private function getBalanceSheetData(Request $request)
    {
        $managerId = Auth::id();
        $asOfDate = $request->input('as_of_date') ? Carbon::parse($request->input('as_of_date')) : Carbon::today();
        $totalIncome = Account::where('type', 'Income')->first()?->generalLedgerTransactions()->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->where('transaction_date', '<=', $asOfDate)->sum('credit') ?? 0;
        $totalExpenses = Account::where('type', 'Expense')->first()?->generalLedgerTransactions()->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->where('transaction_date', '<=', $asOfDate)->sum('debit') ?? 0;
        $netIncome = $totalIncome - $totalExpenses;
        $accountQuery = function ($type) use ($asOfDate, $managerId) {
            return Account::where('type', $type)->whereHas('generalLedgerTransactions.loan', fn($q) => $q->where('loan_manager_id', $managerId))
                ->withSum(['generalLedgerTransactions as debit_total' => fn($q) => $q->where('transaction_date', '<=', $asOfDate)->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'debit')
                ->withSum(['generalLedgerTransactions as credit_total' => fn($q) => $q->where('transaction_date', '<=', $asOfDate)->whereHas('loan', fn($sq) => $sq->where('loan_manager_id', $managerId))], 'credit')
                ->get();
        };
        $assets = $accountQuery('Asset');
        foreach ($assets as $account) { $account->balance = $account->debit_total - $account->credit_total; }
        $liabilities = $accountQuery('Liability');
        foreach ($liabilities as $account) { $account->balance = $account->credit_total - $account->debit_total; }
        $equityAccounts = $accountQuery('Equity');
        foreach ($equityAccounts as $account) { $account->balance = $account->credit_total - $account->debit_total; }
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equityAccounts->sum('balance') + $netIncome;
        return ['assets' => $assets, 'liabilities' => $liabilities, 'equityAccounts' => $equityAccounts, 'netIncome' => $netIncome, 'totalAssets' => $totalAssets, 'totalLiabilities' => $totalLiabilities, 'totalEquity' => $totalEquity, 'totalLiabilitiesAndEquity' => $totalLiabilities + $totalEquity, 'asOfDate' => $asOfDate];
    }
    
    // --- AGING ANALYSIS METHODS ---
    public function agingAnalysis()
    {
        $data = $this->getAgingAnalysisData();
        return view('loan-manager.reports.aging-analysis', $data);
    }
    public function downloadAgingAnalysis()
    {
        $data = $this->getAgingAnalysisData();
        $pdf = Pdf::loadView('reports.pdf.aging-analysis', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('loan-aging-analysis-'.now()->format('Y-m-d').'.pdf');
    }
    private function getAgingAnalysisData()
    {
        $managerId = Auth::id();
        $activeLoans = Loan::where('loan_manager_id', $managerId)->where('status', '!=', 'paid')->with('client', 'payments', 'guarantors')->get();
        $analyzedLoans = [];
        foreach ($activeLoans as $loan) {
            $principal = $loan->principal_amount;
            $totalInterest = $principal * ($loan->interest_rate / 100);
            $totalRepayable = $principal + $totalInterest;
            $term = $loan->term > 0 ? $loan->term : 1;
            $paymentPerPeriod = $totalRepayable / $term;
            $startDate = Carbon::parse($loan->start_date);
            $today = Carbon::today();
            $periodsPassed = 0;
            switch ($loan->repayment_frequency) {
                case 'Daily': $periodsPassed = $startDate->diffInDays($today); break;
                case 'Weekly': $periodsPassed = $startDate->diffInWeeks($today); break;
                default: $periodsPassed = $startDate->diffInMonths($today); break;
            }
            $periodsPassed = min($periodsPassed, $term);
            $expectedPaid = $paymentPerPeriod * $periodsPassed;
            $actualPaid = $loan->payments->sum('amount_paid');
            $arrears = max(0, $expectedPaid - $actualPaid);
            $daysMissed = 0;
            if ($arrears > 0) {
                $firstUnpaidPeriod = floor($actualPaid / $paymentPerPeriod) + 1;
                $firstMissedDueDate = $startDate->copy();
                switch ($loan->repayment_frequency) {
                    case 'Daily': $firstMissedDueDate->addDays($firstUnpaidPeriod); break;
                    case 'Weekly': $firstMissedDueDate->addWeeks($firstUnpaidPeriod); break;
                    default: $firstMissedDueDate->addMonths($firstUnpaidPeriod); break;
                }
                if($today->isAfter($firstMissedDueDate)) { $daysMissed = $firstMissedDueDate->diffInDays($today); }
            }
            $analyzedLoans[] = (object)['loan' => $loan, 'balance' => $totalRepayable - $actualPaid, 'total_arrears' => $arrears, 'days_missed' => $daysMissed];
        }
        return ['analyzedLoans' => $analyzedLoans];
    }

    // --- DAILY REPORT METHODS ---
    public function dailyReport(Request $request)
    {
        $data = $this->getDailyReportData($request);
        return view('loan-manager.reports.daily-report', $data);
    }
    public function downloadDailyReport(Request $request)
    {
        $data = $this->getDailyReportData($request);
        $pdf = Pdf::loadView('reports.pdf.daily-report', $data);
        return $pdf->stream('daily-report-'.$data['reportDate'].'.pdf');
    }
    private function getDailyReportData(Request $request)
    {
        $managerId = Auth::id();
        $reportDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $loansGiven = Loan::where('loan_manager_id', $managerId)->whereDate('start_date', $reportDate)->with('client')->get();
        $paymentsReceived = Payment::whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->whereDate('payment_date', $reportDate)->with('loan.client')->get();
        $summary = ['total_loaned_principal' => $loansGiven->sum('principal_amount'), 'total_processing_fees' => $loansGiven->sum('processing_fee'), 'total_payments_received' => $paymentsReceived->sum('amount_paid'), 'count_loans_given' => $loansGiven->count(), 'count_payments_received' => $paymentsReceived->count()];
        return ['reportDate' => $reportDate->toDateString(), 'loansGiven' => $loansGiven, 'paymentsReceived' => $paymentsReceived, 'summary' => $summary];
    }
}