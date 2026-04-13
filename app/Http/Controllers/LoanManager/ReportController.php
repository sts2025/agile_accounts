<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\CashTransaction;
use App\Models\LoanManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    // === DAILY REPORT ===
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
        $manager = Auth::user()->loanManager;
        $reportDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        $loansGiven = $manager->loans()->whereDate('start_date', $reportDate)->with('client')->get();
        $paymentsReceived = $manager->payments()->whereDate('payment_date', $reportDate)->with('loan.client')->get();

        $cashInflows = $manager->cashTransactions()->where('type', 'inflow')->whereDate('transaction_date', $reportDate)->get();
        $cashOutflows = $manager->cashTransactions()->where('type', 'outflow')->whereDate('transaction_date', $reportDate)->get();

        $summary = [
            'total_loaned_principal' => $loansGiven->sum('principal_amount'),
            'total_processing_fees' => $loansGiven->sum('processing_fee'),
            'total_payments_received' => $paymentsReceived->sum('amount_paid'),
            'total_other_inflows' => $cashInflows->sum('amount'),
            'total_expenses_outflows' => $cashOutflows->sum('amount'),
            'count_loans_given' => $loansGiven->count(),
            'count_payments_received' => $paymentsReceived->count()
        ];

        return [
            'reportDate' => $reportDate->toDateString(),
            'loansGiven' => $loansGiven,
            'paymentsReceived' => $paymentsReceived,
            'cashInflows' => $cashInflows,
            'cashOutflows' => $cashOutflows,
            'summary' => $summary
        ];
    }


    // === PROFIT & LOSS REPORT ===
    public function profitAndLoss(Request $request)
    {
        $data = $this->getProfitAndLossData($request);
        return view('loan-manager.reports.profit-and-loss', $data);
    }

    public function downloadProfitAndLoss(Request $request)
    {
        $data = $this->getProfitAndLossData($request);
        $pdf = Pdf::loadView('reports.pdf.profit-and-loss', $data);
        return $pdf->stream('profit-and-loss-'.$data['startDate'].'-to-'.$data['endDate'].'.pdf');
    }

    private function getProfitAndLossData(Request $request)
    {
        $manager = auth()->user()->loanManager;
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // --- INCOME ---
        // 1. Interest Income
        $loans = $manager->loans()->whereBetween('start_date', [$startDate, $endDate])->get();
        $totalInterest = $loans->sum(function ($loan) {
            return $loan->principal_amount * ($loan->interest_rate / 100);
        });
        
        // 2. Processing Fees
        $totalProcessingFee = $loans->sum('processing_fee');
        
        $loanIncome = collect([
            (object)[ 'name' => 'Loan Interest Income', 'period_total' => $totalInterest ],
            (object)[ 'name' => 'Processing Fee Income', 'period_total' => $totalProcessingFee ],
        ]);

        // 3. Other Inflows (Excluding equity/savings)
        $otherIncome = $manager->cashTransactions()
            ->where('type', 'inflow')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get()
            ->toBase() // FIX: Converts Eloquent collection to standard collection to prevent getKey() error
            ->filter(function($tx) {
                $desc = strtolower($tx->description);
                return !str_contains($desc, 'capital') && 
                       !str_contains($desc, 'grant') && 
                       !str_contains($desc, 'donation') && 
                       !str_contains($desc, 'saving');
            })
            ->groupBy('description') 
            ->map(function ($group, $name) {
                return (object)['name' => $name ?: 'Other Income', 'period_total' => $group->sum('amount')];
            })->values();

        // FIX: Use concat() instead of merge()
        $incomeAccounts = $loanIncome->concat($otherIncome);

        // --- EXPENSES ---
        // 1. Expenses from Expenses Table
        $categorizedExpenses = $manager->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            ->toBase() // FIX
            ->groupBy(function($expense) {
                return $expense->category->name ?? 'Uncategorized';
            })
            ->map(function ($group, $categoryName) {
                return (object)['name' => $categoryName, 'period_total' => $group->sum('amount')];
            })->values();

        // 2. Outflows
        $otherExpenses = $manager->cashTransactions()
            ->where('type', 'outflow')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get()
            ->toBase() // FIX
            ->groupBy('description')
            ->map(function ($group, $name) {
                return (object)['name' => $name ?: 'General Expenses', 'period_total' => $group->sum('amount')];
            })->values();

        // FIX: Use concat() instead of merge()
        $expenseAccounts = $categorizedExpenses->concat($otherExpenses);

        // --- TOTALS ---
        $totalIncome = $incomeAccounts->sum('period_total');
        $totalExpenses = $expenseAccounts->sum('period_total');
        $netProfit = $totalIncome - $totalExpenses;
        $currency = $manager->currency_symbol ?? 'UGX';

        $formattedStartDate = Carbon::parse($startDate)->format('d M, Y');
        $formattedEndDate = Carbon::parse($endDate)->format('d M, Y');
        
        $whatsappMessage = urlencode(
            "Profit & Loss Report ({$formattedStartDate} to {$formattedEndDate})\n\n" .
            "Total Income: " . number_format($totalIncome, 0) . " " . $currency . "\n" .
            "Total Expenses: " . number_format($totalExpenses, 0) . " " . $currency . "\n" .
            "Net Profit: " . number_format($netProfit, 0) . " " . $currency
        );

        return compact(
            'incomeAccounts', 'expenseAccounts', 'totalIncome', 'totalExpenses',
            'netProfit', 'startDate', 'endDate', 'whatsappMessage'
        );
    }


    // === BALANCE SHEET ===
    public function balanceSheet(Request $request)
    {
        $manager = auth()->user()->loanManager;
        // Ensure we refresh the manager to get the latest opening_balance
        $manager->refresh();
        
        $reportDate = $request->input('report_date', now()->toDateString());

        // --- 1. ASSETS ---
        // A. Outstanding Principal
        $activeLoans = $manager->loans()
            ->where('status', 'active')
            ->where('start_date', '<=', $reportDate)
            ->get();
            
        $outstandingPrincipal = 0;
        foreach($activeLoans as $loan) {
            $totalDue = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100));
            $paid = $loan->payments()->where('payment_date', '<=', $reportDate)->sum('amount_paid');
            $outstandingPrincipal += max(0, $totalDue - $paid);
        }

        // B. Cash Logic
        $loansPaid = $manager->payments()->where('payment_date', '<=', $reportDate)->sum('amount_paid');
        $otherInflows = $manager->cashTransactions()->where('type', 'inflow')->where('transaction_date', '<=', $reportDate)->sum('amount');
        $bankWithdrawals = $manager->bankTransactions()->where('type', 'Withdrawal')->where('deposit_date', '<=', $reportDate)->sum('amount');
        
        $loansGiven = $manager->loans()->where('start_date', '<=', $reportDate)->sum('principal_amount');
        $expensesPaid = $manager->expenses()->where('expense_date', '<=', $reportDate)->sum('amount');
        $otherOutflows = $manager->cashTransactions()->where('type', 'outflow')->where('transaction_date', '<=', $reportDate)->sum('amount');
        $bankDeposits = $manager->bankTransactions()->where('type', 'Deposit')->where('deposit_date', '<=', $reportDate)->sum('amount');

        // Balances
        $openingBalance = $manager->opening_balance ?? 0;
        
        // Cash at hand = Opening + In - Out
        $cashOnHand = $openingBalance + ($loansPaid + $otherInflows + $bankWithdrawals) - ($loansGiven + $expensesPaid + $otherOutflows + $bankDeposits);
        
        $cashAtBank = $bankDeposits - $bankWithdrawals;

        $assets = collect([
            (object)['name' => 'Principal Amount (Loan Portfolio)', 'balance' => $outstandingPrincipal],
            (object)['name' => 'Cash At Hand', 'balance' => $cashOnHand],
            (object)['name' => 'Cash at Bank', 'balance' => $cashAtBank],
            (object)['name' => 'Receivables', 'balance' => 0],
        ]);
        $totalAssets = $assets->sum('balance');


        // --- 2. LIABILITIES ---
        $savings = $manager->cashTransactions()
            ->where('type', 'inflow')
            ->where('description', 'like', '%Savings%')
            ->where('transaction_date', '<=', $reportDate)
            ->sum('amount');
            
        $payables = 0;

        $liabilities = collect([
            (object)['name' => 'Savings', 'balance' => $savings],
            (object)['name' => 'Payings (Payables)', 'balance' => $payables],
        ]); 
        $totalLiabilities = $liabilities->sum('balance');


        // --- 3. EQUITY ---
        $capital = $openingBalance; 
        $shares = 0;
        
        // Retained Earnings derived from P&L
        $plRequest = new Request(['start_date' => '2000-01-01', 'end_date' => $reportDate]);
        $plData = $this->getProfitAndLossData($plRequest);
        $retainedEarnings = $plData['netProfit'];

        $equity = collect([
            (object)['name' => 'Opening Balance (Capital)', 'balance' => $capital],
            (object)['name' => 'Shares', 'balance' => $shares],
            (object)['name' => 'Retained Earnings', 'balance' => $retainedEarnings],
        ]);
        
        // Balancing logic
        $totalEquity = $equity->sum('balance');
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $unbalancedAmount = $totalAssets - $totalLiabilitiesAndEquity;
        
        if (abs($unbalancedAmount) > 0.01) {
             $equity->push((object)['name' => 'Balancing Adjustment', 'balance' => $unbalancedAmount]);
             $totalEquity += $unbalancedAmount;
             $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        }

        return view('loan-manager.reports.balance-sheet', 
            compact('assets', 'liabilities', 'equity', 'reportDate', 'totalAssets', 'totalLiabilities', 'totalEquity', 'totalLiabilitiesAndEquity')
        );
    }


    // === GENERAL LEDGER ===
    public function generalLedger(Request $request)
    {
        $manager = auth()->user()->loanManager;
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $masterTransactionList = new Collection();

        // 1. Loans Given
        $loans = $manager->loans()->whereBetween('start_date', [$startDate, $endDate])->with('client')->get();
        foreach ($loans as $loan) {
            $masterTransactionList->push((object)[
                'date' => $loan->start_date,
                'description' => "Loan Disbursed: " . $loan->client->name,
                'amount_out' => $loan->principal_amount,
                'amount_in' => 0,
            ]);
        }

        // 2. Payments
        $payments = $manager->payments()->whereBetween('payment_date', [$startDate, $endDate])->with('loan.client')->get();
        foreach ($payments as $payment) {
            $masterTransactionList->push((object)[
                'date' => $payment->payment_date,
                'description' => "Loan Repayment: " . $payment->loan->client->name,
                'amount_out' => 0,
                'amount_in' => $payment->amount_paid,
            ]);
        }

        // 3. Cash Transactions
        $txs = $manager->cashTransactions()->whereBetween('transaction_date', [$startDate, $endDate])->get();
        foreach ($txs as $tx) {
            $masterTransactionList->push((object)[
                'date' => $tx->transaction_date,
                'description' => ($tx->type == 'inflow' ? 'Inflow: ' : 'Outflow: ') . $tx->description,
                'amount_out' => $tx->type == 'outflow' ? $tx->amount : 0,
                'amount_in' => $tx->type == 'inflow' ? $tx->amount : 0,
            ]);
        }
        
        // 4. Expenses
        $expenses = $manager->expenses()->whereBetween('expense_date', [$startDate, $endDate])->with('category')->get();
        foreach ($expenses as $exp) {
            $masterTransactionList->push((object)[
                'date' => $exp->expense_date,
                'description' => "Expense: " . ($exp->category->name ?? 'Misc'),
                'amount_out' => $exp->amount,
                'amount_in' => 0,
            ]);
        }

        $transactions = $masterTransactionList->sortBy('date');

        return view('loan-manager.reports.general-ledger', compact('transactions', 'startDate', 'endDate'));
    }


    // === TRIAL BALANCE (FIXED) ===
    public function trialBalance(Request $request)
    {
        $manager = auth()->user()->loanManager;
        $manager->refresh(); // Refresh to get latest opening balance
        
        $endDate = $request->input('end_date', now()->toDateString());

        // 1. DEBITS (Assets + Expenses)
        // Note: Balance Sheet expects 'report_date'
        $bsReq = new Request(['report_date' => $endDate]); 
        $bsData = $this->balanceSheet($bsReq); 
        
        $assets = $bsData['assets'] ?? collect([]);
        
        // Re-request P&L for expense data
        $plReqDate = new Request(['end_date' => $endDate]);
        $expenses = $this->getProfitAndLossData($plReqDate)['expenseAccounts'];

        // 2. CREDITS (Liabilities + Equity + Income)
        $liabilities = $bsData['liabilities'] ?? collect([]);
        $income = $this->getProfitAndLossData($plReqDate)['incomeAccounts'];

        // Construct Account List
        $accounts = collect([]);

        // Add Assets (Dr)
        foreach($assets as $asset) {
            if($asset->balance != 0) {
                $accounts->push((object)['name' => $asset->name, 'debit' => $asset->balance, 'credit' => 0]);
            }
        }
        // Add Expenses (Dr)
        foreach($expenses as $exp) {
             if($exp->period_total != 0) {
                $accounts->push((object)['name' => 'Exp: ' . $exp->name, 'debit' => $exp->period_total, 'credit' => 0]);
            }
        }

        // Add Liabilities (Cr)
        foreach($liabilities as $liab) {
             if($liab->balance != 0) {
                $accounts->push((object)['name' => $liab->name, 'debit' => 0, 'credit' => $liab->balance]);
            }
        }
        
        // Add Equity (Cr) - FIX: Use Manager Opening Balance explicitly
        $openingBalance = $manager->opening_balance ?? 0;
        if($openingBalance > 0) {
            $accounts->push((object)['name' => 'Opening Balance (Equity)', 'debit' => 0, 'credit' => $openingBalance]);
        }

        // Add Income (Cr)
        foreach($income as $inc) {
            if($inc->period_total != 0) {
                $accounts->push((object)['name' => 'Inc: ' . $inc->name, 'debit' => 0, 'credit' => $inc->period_total]);
            }
        }

        $totalDebits = $accounts->sum('debit');
        $totalCredits = $accounts->sum('credit');

        return view('loan-manager.reports.trial-balance', compact('accounts', 'totalDebits', 'totalCredits'));
    }


    // === LOAN AGING (Unchanged) ===
    public function loanAging()
    {
        $manager = Auth::user()->loanManager;
        $overdueLoans = [];
        
        $activeLoans = $manager->loans()
            ->where('status', 'active')
            ->with(['repaymentSchedules', 'client', 'payments'])
            ->get();

        foreach ($activeLoans as $loan) {
            $totalDueToDate = $loan->repaymentSchedules()->where('due_date', '<=', now())->sum('amount'); 
            $totalPaid = $loan->payments->sum('amount_paid');
            $arrears = $totalDueToDate - $totalPaid;
            
            if ($arrears > 0.01) { 
                $firstMissedSchedule = $loan->repaymentSchedules()
                    ->where('due_date', '<', now())
                    ->orderBy('due_date', 'asc')
                    ->first();
                    
                $loan->days_missed = $firstMissedSchedule ? Carbon::parse($firstMissedSchedule->due_date)->diffInDays(now()) : 0;
                $loan->arrears = $arrears;
                
                $totalRepayable = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100)) + ($loan->processing_fee ?? 0);
                $loan->total_balance = max(0, $totalRepayable - $totalPaid);
                
                $overdueLoans[] = $loan;
            }
        }
        
        return view('loan-manager.reports.loan-aging', ['loans' => $overdueLoans]);
    }

    // === PRINT FORMS ===
    public function showPrintForms(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;
        $clientsWithLoans = \App\Models\Client::where('loan_manager_id', $managerId)->whereHas('loans')->with('loans')->orderBy('name')->get();
        return view('loan-manager.reports.print-forms', compact('clientsWithLoans'));
    }
}