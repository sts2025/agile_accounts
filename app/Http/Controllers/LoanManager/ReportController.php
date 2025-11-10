<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\BankTransaction;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use App\Models\LoanManager;

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

        $summary = [
            'total_loaned_principal' => $loansGiven->sum('principal_amount'),
            'total_processing_fees' => $loansGiven->sum('processing_fee'),
            'total_payments_received' => $paymentsReceived->sum('amount_paid'),
            'count_loans_given' => $loansGiven->count(),
            'count_payments_received' => $paymentsReceived->count()
        ];

        return [
            'reportDate' => $reportDate->toDateString(),
            'loansGiven' => $loansGiven,
            'paymentsReceived' => $paymentsReceived,
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
        $loans = $manager->loans()->whereBetween('start_date', [$startDate, $endDate])->get();
        $totalInterest = $loans->sum(function ($loan) {
            return $loan->principal_amount * ($loan->interest_rate / 100);
        });
        $totalProcessingFee = $loans->sum('processing_fee');
        $loanIncome = collect([
            (object)[ 'name' => 'Loan Interest Income', 'period_total' => $totalInterest ],
            (object)[ 'name' => 'Processing Fee Income', 'period_total' => $totalProcessingFee ],
        ]);
        $receivables = $manager->cashTransactions()
            ->where('type', 'receivable')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get()
            ->groupBy('description')
            ->map(function ($group, $name) {
                return (object)['name' => $name, 'period_total' => $group->sum('amount')];
            })->values();
        $incomeAccounts = $loanIncome->merge($receivables);

        // --- EXPENSES ---
        $categorizedExpenses = $manager->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            ->groupBy(function($expense) {
                return $expense->category->name ?? 'Uncategorized';
            })
            ->map(function ($group, $categoryName) {
                return (object)['name' => $categoryName, 'period_total' => $group->sum('amount')];
            })->values();
        $payables = $manager->cashTransactions()
            ->where('type', 'payable')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get()
            ->groupBy('description')
            ->map(function ($group, $name) {
                return (object)['name' => $name, 'period_total' => $group->sum('amount')];
            })->values();
        $expenseAccounts = $categorizedExpenses->merge($payables);

        // --- TOTALS ---
        $totalIncome = $incomeAccounts->sum('period_total');
        $totalExpenses = $expenseAccounts->sum('period_total');
        $netProfit = $totalIncome - $totalExpenses;
        $formattedStartDate = Carbon::parse($startDate)->format('d M, Y');
        $formattedEndDate = Carbon::parse($endDate)->format('d M, Y');
        $whatsappMessage = urlencode(
            "Profit & Loss Report ({$formattedStartDate} to {$formattedEndDate})\n\n" .
            "Total Income: " . number_format($totalIncome, 0) . " " . LoanManager::getCurrency() . "\n" .
            "Total Expenses: " . number_format($totalExpenses, 0) . " " . LoanManager::getCurrency() . "\n" .
            "Net Profit: " . number_format($netProfit, 0) . " " . LoanManager::getCurrency()
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
        $reportDate = $request->input('report_date', now()->toDateString());

        // --- ASSETS ---
        $activeLoans = $manager->loans()->where('status', 'active')->where('start_date', '<=', $reportDate)->get();
        $loansReceivable = $activeLoans->sum(function ($loan) use ($reportDate) {
            $totalRepayable = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100));
            
            // FIX: Payments column is 'amount_paid'
            $totalPaid = $loan->payments()->where('payment_date', '<=', $reportDate)->sum('amount_paid'); 
            
            $balance = $totalRepayable - $totalPaid;
            return ($balance > 0) ? $balance : 0;
        });

        // FIX: Bank Transaction column remains 'deposit_date'
        $bankDeposits = $manager->bankTransactions()
            ->where('type', 'Deposit')
            ->where('deposit_date', '<=', $reportDate)->sum('amount'); 
        $bankWithdrawals = $manager->bankTransactions()
            ->where('type', 'Withdrawal')
            ->where('deposit_date', '<=', $reportDate)->sum('amount'); 
        $bankBalance = $bankDeposits - $bankWithdrawals;

        // FIX: Payments column is 'amount_paid'
        $paymentsReceived = $manager->payments()->where('payment_date', '<=', $reportDate)->sum('amount_paid'); 
        
        $receivables = $manager->cashTransactions()->where('type', 'receivable')->where('transaction_date', '<=', $reportDate)->sum('amount');
        
        // Cash Flow components calculations
        $cashFromBank = $bankWithdrawals; 
        $loansGiven = $manager->loans()->where('start_date', '<=', $reportDate)->sum('principal_amount'); 
        $expensesPaid = $manager->expenses()->where('expense_date', '<=', $reportDate)->sum('amount');
        $payablesPaid = $manager->cashTransactions()->where('type', 'payable')->where('transaction_date', '<=', $reportDate)->sum('amount');
        $cashToBank = $bankDeposits; 
        $cashOnHand = ($paymentsReceived + $receivables + $cashFromBank) - ($loansGiven + $expensesPaid + $payablesPaid + $cashToBank);

        $assets = collect([
            (object)['name' => 'Cash on Hand', 'balance' => $cashOnHand],
            (object)['name' => 'Bank Balance', 'balance' => $bankBalance],
            (object)['name' => 'Loans Receivable', 'balance' => $loansReceivable],
        ]);
        $totalAssets = $assets->sum('balance');

        // --- LIABILITIES & EQUITY ---
        $plRequest = new Request(['start_date' => '2000-01-01', 'end_date' => $reportDate]);
        $plData = $this->getProfitAndLossData($plRequest);
        $netProfit = $plData['netProfit'];
        $liabilities = collect([]); 
        $totalLiabilities = 0;
        $equity = collect([
            (object)['name' => 'Retained Earnings (Net Profit)', 'balance' => $netProfit],
        ]);
        $totalEquity = $equity->sum('balance');
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $unbalancedAmount = $totalAssets - $totalLiabilitiesAndEquity;
        if (abs($unbalancedAmount) > 0.01) {
             $equity->push((object)['name' => 'Balancing Item (Unaccounted for)', 'balance' => $unbalancedAmount]);
             $totalEquity += $unbalancedAmount;
             $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        }

        return view('loan-manager.reports.balance-sheet', 
            compact('assets', 'liabilities', 'equity', 'reportDate', 'totalAssets', 'totalLiabilities', 'totalEquity', 'totalLiabilitiesAndEquity')
        );
    }


    // === GENERAL LEDGER (MASTER TRANSACTION LIST) ===
    public function generalLedger(Request $request)
    {
        $manager = auth()->user()->loanManager;
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $masterTransactionList = new Collection();

        // 1. Loans Given (Cash Out)
        $loans = $manager->loans()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with('client')
            ->get();
        foreach ($loans as $loan) {
            $masterTransactionList->push((object)[
                'date' => $loan->start_date,
                'description' => "Loan Disbursed to: " . $loan->client->name,
                'amount_out' => $loan->principal_amount,
                'amount_in' => 0,
            ]);
        }

        // 2. Payments Received (Cash In)
        $payments = $manager->payments()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with('loan.client')
            ->get();
        foreach ($payments as $payment) {
            $masterTransactionList->push((object)[
                'date' => $payment->payment_date,
                'description' => "Payment from: " . $payment->loan->client->name,
                'amount_out' => 0,
                'amount_in' => $payment->amount_paid, // FIX: Payments column is 'amount_paid'
            ]);
        }
        
        // 3. Expenses Paid (Cash Out)
        $expenses = $manager->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category')
            ->get();
        foreach ($expenses as $expense) {
            $masterTransactionList->push((object)[
                'date' => $expense->expense_date,
                'description' => "Expense: " . ($expense->category->name ?? 'Uncategorized'),
                'amount_out' => $expense->amount,
                'amount_in' => 0,
            ]);
        }

        // 4. Payables & Receivables (Cash Out / Cash In)
        $transfers = $manager->cashTransactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
        foreach ($transfers as $transfer) {
            $masterTransactionList->push((object)[
                'date' => $transfer->transaction_date,
                'description' => $transfer->description,
                'amount_out' => $transfer->type == 'payable' ? $transfer->amount : 0,
                'amount_in' => $transfer->type == 'receivable' ? $transfer->amount : 0,
            ]);
        }

        // 5. Bank Transactions (Internal Transfer)
        $bankTxs = $manager->bankTransactions()
            ->whereBetween('deposit_date', [$startDate, $endDate]) // FIX: Using 'deposit_date'
            ->get();
        foreach ($bankTxs as $tx) {
            if ($tx->type == 'Deposit') {
                $description = "Cash moved to Bank: " . $tx->description;
                $amount_out = $tx->amount; // Cash on hand goes *out*
                $amount_in = 0;
            } else {
                $description = "Cash moved from Bank: " . $tx->description;
                $amount_out = 0;
                $amount_in = $tx->amount; // Cash on hand comes *in*
            }
            $masterTransactionList->push((object)[
                'date' => $tx->deposit_date, // FIX: Using 'deposit_date'
                'description' => $description,
                'amount_out' => $amount_out,
                'amount_in' => $amount_in,
            ]);
        }

        // Sort the final list by date
        $transactions = $masterTransactionList->sortBy('date');

        return view('loan-manager.reports.general-ledger', compact('transactions', 'startDate', 'endDate'));
    }


    // === TRIAL BALANCE (RESTORED ORIGINAL BROKEN FUNCTIONALITY) ===
    public function trialBalance()
    {
        $accounts = Account::with('generalLedgerTransactions')->get();
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = $account->generalLedgerTransactions()->sum('debit');
            $credits = $account->generalLedgerTransactions()->sum('credit');
            $balance = $debits - $credits;

            if ($balance > 0) {
                $account->debit_balance = $balance;
                $account->credit_balance = 0;
                $totalDebits += $balance;
            } else {
                $account->debit_balance = 0;
                $account->credit_balance = abs($balance);
                $totalCredits += abs($balance);
            }
        }

        return view('loan-manager.reports.trial-balance', compact('accounts', 'totalDebits', 'totalCredits'));
    }


    // === LOAN AGING ===
    public function loanAging()
    {
        $manager = Auth::user()->loanManager;
        $overdueLoans = [];
        
        // Ensure ALL necessary relationships are loaded: client, schedules, payments, GUARANTORS.
        $activeLoans = $manager->loans()
            ->where('status', 'active')
            ->with(['repaymentSchedules', 'client', 'payments', 'guarantors'])
            ->get();

        foreach ($activeLoans as $loan) {
            // FIX 1: Correct column name is 'amount' for repayment schedules sum.
            $totalDueToDate = $loan->repaymentSchedules()->where('due_date', '<=', now())->sum('amount'); 
            
            // Calculate what has actually been paid
            $totalPaid = $loan->payments->sum('amount_paid');
            
            // Arrears is the difference
            $arrears = $totalDueToDate - $totalPaid;
            
            // Check if loan is actually overdue
            if ($arrears > 0.01) { 
                
                // Find the first missed scheduled payment date (complex but robust logic)
                // This logic identifies if the cumulative payments are less than the amount *required* up to that schedule.
                $firstMissedSchedule = $loan->repaymentSchedules()
                    ->where('due_date', '<', now())
                    ->whereRaw('? > (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE loan_id = repayment_schedules.loan_id AND payment_date <= repayment_schedules.due_date)', [$totalDueToDate])
                    ->orderBy('due_date', 'asc')
                    ->first();
                    
                $loan->days_missed = $firstMissedSchedule ? Carbon::parse($firstMissedSchedule->due_date)->diffInDays(now()) : 0;
                
                $loan->arrears = $arrears;
                
                // Calculate Total Balance for display purposes (Total Repayable - Total Paid)
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
        $clientsWithLoans = Client::where('loan_manager_id', $managerId)->whereHas('loans')->with('loans')->orderBy('name')->get();
        return view('loan-manager.reports.print-forms', compact('clientsWithLoans'));
    }
}