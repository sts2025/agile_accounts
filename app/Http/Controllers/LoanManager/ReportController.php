<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\CashTransaction;
use App\Models\LoanManager;
use App\Models\MfiTransaction;
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

        // Today's savings cash movement — needed alongside loan disbursements/
        // repayments for the manager to reconcile actual cash in the drawer.
        $savingsDeposited = MfiTransaction::where('loan_manager_id', $manager->id)
            ->where('transaction_type', 'deposit')
            ->whereDate('transaction_date', $reportDate)
            ->sum('credit');
        $savingsWithdrawn = MfiTransaction::where('loan_manager_id', $manager->id)
            ->where('transaction_type', 'withdrawal')
            ->whereDate('transaction_date', $reportDate)
            ->sum('debit');

        $summary = [
            'total_loaned_principal' => $loansGiven->sum('principal_amount'),
            'total_processing_fees' => $loansGiven->sum('processing_fee'),
            'total_payments_received' => $paymentsReceived->sum('amount_paid'),
            'total_other_inflows' => $cashInflows->sum('amount'),
            'total_expenses_outflows' => $cashOutflows->sum('amount'),
            'count_loans_given' => $loansGiven->count(),
            'count_payments_received' => $paymentsReceived->count(),
            'total_savings_deposited' => $savingsDeposited,
            'total_savings_withdrawn' => $savingsWithdrawn,
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
        // 1. Interest Income (FIXED: Now only counts ACTUAL interest paid, not theoretical interest)
        $payments = $manager->payments()->whereBetween('payment_date', [$startDate, $endDate])->get();
        $totalInterest = $payments->sum('interest_paid');
        
        // 2. Processing Fees (Cash collected at loan creation)
        $loans = $manager->loans()->whereBetween('start_date', [$startDate, $endDate])->get();
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
            ->toBase() 
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

        $incomeAccounts = $loanIncome->concat($otherIncome);

        // --- EXPENSES ---
        // 1. Expenses from Expenses Table
        $categorizedExpenses = $manager->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category')
            ->get()
            ->toBase() 
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
            ->toBase() 
            ->groupBy('description')
            ->map(function ($group, $name) {
                return (object)['name' => $name ?: 'General Expenses', 'period_total' => $group->sum('amount')];
            })->values();

        // 3. Interest Expense (Savings) — non-cash: interest posted to client
        // savings accounts via the End-of-Period run. This is a real cost of
        // holding client deposits, and offsets the corresponding non-cash
        // increase to the Client Savings liability on the Balance Sheet so
        // the books stay balanced without any cash actually moving.
        $interestExpense = MfiTransaction::where('loan_manager_id', $manager->id)
            ->where('transaction_type', 'interest')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('credit');

        $interestExpenseAccounts = $interestExpense > 0
            ? collect([(object)['name' => 'Interest Expense (Savings)', 'period_total' => $interestExpense]])
            : collect([]);

        $expenseAccounts = $categorizedExpenses->concat($otherExpenses)->concat($interestExpenseAccounts);

        // --- TOTALS ---
        $totalIncome = $incomeAccounts->sum('period_total');
        $totalExpenses = $expenseAccounts->sum('period_total');
        
        // NET PROFIT IS NOW 100% CORRECT: Interest Paid + Fees - Expenses (Principal is excluded)
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
    // FIX: Separated the data calculation from the View return so Trial Balance can access it
    private function getBalanceSheetData(Request $request)
    {
        $manager = auth()->user()->loanManager;
        $manager->refresh();
        
        $reportDate = $request->input('report_date', now()->toDateString());

        // --- 1. ASSETS ---
        // Loan Portfolio: the outstanding PRINCIPAL balance only (principal
        // disbursed minus principal actually repaid). This used to be
        // computed as "total repayable" (principal + full-term interest)
        // minus total cash paid, which baked uncollected, unearned interest
        // into the asset figure with no matching entry on the equity side
        // (interest income is recognised below on a cash basis — only when
        // actually paid). That mismatch was the single biggest cause of the
        // "Balancing Adjustment" plug on this report.
        //
        // Defaulted loans keep their remaining principal on the books too,
        // shown separately as Non-Performing Loans, rather than silently
        // vanishing from assets: the cash was genuinely disbursed and the
        // debt still legally exists until the manager formally writes it
        // off (a distinct action, not built yet).
        $openLoans = $manager->loans()
            ->whereIn('status', ['active', 'defaulted'])
            ->where('start_date', '<=', $reportDate)
            ->get();

        $performingPrincipal = 0;
        $nonPerformingPrincipal = 0;
        foreach ($openLoans as $loan) {
            $principalPaid = $loan->payments()->where('payment_date', '<=', $reportDate)->sum('principal_paid');
            $remaining = max(0, $loan->principal_amount - $principalPaid);

            if ($loan->status === 'defaulted') {
                $nonPerformingPrincipal += $remaining;
            } else {
                $performingPrincipal += $remaining;
            }
        }

        // B. Cash Logic
        $loansPaid = $manager->payments()->where('payment_date', '<=', $reportDate)->sum('amount_paid');
        $otherInflows = $manager->cashTransactions()->where('type', 'inflow')->where('transaction_date', '<=', $reportDate)->sum('amount');
        $bankWithdrawals = $manager->bankTransactions()->where('type', 'Withdrawal')->where('deposit_date', '<=', $reportDate)->sum('amount');
        // Processing fees are collected in cash at disbursement, separately
        // from the principal, and are already counted as income in the P&L
        // below — but the matching cash inflow was missing here, so every
        // fee-charging loan permanently threw the books off by exactly the
        // fee amount.
        $feesCollected = $manager->loans()->where('start_date', '<=', $reportDate)->sum('processing_fee');

        $loansGiven = $manager->loans()->where('start_date', '<=', $reportDate)->sum('principal_amount');
        $expensesPaid = $manager->expenses()->where('expense_date', '<=', $reportDate)->sum('amount');
        $otherOutflows = $manager->cashTransactions()->where('type', 'outflow')->where('transaction_date', '<=', $reportDate)->sum('amount');
        $bankDeposits = $manager->bankTransactions()->where('type', 'Deposit')->where('deposit_date', '<=', $reportDate)->sum('amount');

        // MFI savings mobilization: a client depositing into savings is real
        // cash arriving in the till (matched by the "Client Savings" liability
        // below), and a withdrawal is real cash leaving. Neither is income or
        // expense — this only affects the Cash and Liabilities lines.
        $mfiSavingsIn = MfiTransaction::where('loan_manager_id', $manager->id)
            ->where('transaction_type', 'deposit')
            ->where('transaction_date', '<=', $reportDate)
            ->sum('credit');
        $mfiSavingsOut = MfiTransaction::where('loan_manager_id', $manager->id)
            ->where('transaction_type', 'withdrawal')
            ->where('transaction_date', '<=', $reportDate)
            ->sum('debit');

        // Interest posted to client savings (End of Period run) is NOT cash
        // — it's a non-cash increase to what the business owes its savers,
        // offset below by an Interest Expense line in the P&L so the books
        // stay balanced without any cash actually moving.
        $mfiInterestCredited = MfiTransaction::where('loan_manager_id', $manager->id)
            ->where('transaction_type', 'interest')
            ->where('transaction_date', '<=', $reportDate)
            ->sum('credit');

        $openingBalance = $manager->opening_balance ?? 0;
        $cashOnHand = $openingBalance
            + ($loansPaid + $otherInflows + $bankWithdrawals + $mfiSavingsIn + $feesCollected)
            - ($loansGiven + $expensesPaid + $otherOutflows + $bankDeposits + $mfiSavingsOut);
        $cashAtBank = $bankDeposits - $bankWithdrawals;

        $assets = collect([
            (object)['name' => 'Loan Portfolio (Active)', 'balance' => $performingPrincipal],
            (object)['name' => 'Non-Performing Loans (Defaulted)', 'balance' => $nonPerformingPrincipal],
            (object)['name' => 'Cash At Hand', 'balance' => $cashOnHand],
            (object)['name' => 'Cash at Bank', 'balance' => $cashAtBank],
            (object)['name' => 'Receivables', 'balance' => 0],
        ]);
        $totalAssets = $assets->sum('balance');


        // --- 2. LIABILITIES ---
        // Legacy manual workaround: managers who logged savings-like activity
        // as generic cash transactions before the MFI module existed. Kept
        // as-is so existing historical data isn't dropped from the report.
        $savings = $manager->cashTransactions()
            ->where('type', 'inflow')
            ->where('description', 'like', '%Savings%')
            ->where('transaction_date', '<=', $reportDate)
            ->sum('amount');

        // Real MFI client savings liability: what the business owes back to
        // clients, computed from the actual savings ledger rather than a
        // description search. Includes interest credited (non-cash) as well
        // as cash deposits/withdrawals — the client's balance grew either way.
        $mfiClientSavings = $mfiSavingsIn + $mfiInterestCredited - $mfiSavingsOut;

        $payables = 0;

        $liabilities = collect([
            (object)['name' => 'Savings', 'balance' => $savings],
            (object)['name' => 'Client Savings (MFI)', 'balance' => $mfiClientSavings],
            (object)['name' => 'Payings (Payables)', 'balance' => $payables],
        ]);
        $totalLiabilities = $liabilities->sum('balance');


        // --- 3. EQUITY ---
        $capital = $openingBalance; 
        $shares = 0;
        
        $plRequest = new Request(['start_date' => '2000-01-01', 'end_date' => $reportDate]);
        $plData = $this->getProfitAndLossData($plRequest);
        $retainedEarnings = $plData['netProfit'];

        $equity = collect([
            (object)['name' => 'Opening Balance (Capital)', 'balance' => $capital],
            (object)['name' => 'Shares', 'balance' => $shares],
            (object)['name' => 'Retained Earnings', 'balance' => $retainedEarnings],
        ]);
        
        $totalEquity = $equity->sum('balance');
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $unbalancedAmount = $totalAssets - $totalLiabilitiesAndEquity;
        
        if (abs($unbalancedAmount) > 0.01) {
             $equity->push((object)['name' => 'Balancing Adjustment', 'balance' => $unbalancedAmount]);
             $totalEquity += $unbalancedAmount;
             $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        }

        return compact('assets', 'liabilities', 'equity', 'reportDate', 'totalAssets', 'totalLiabilities', 'totalEquity', 'totalLiabilitiesAndEquity');
    }

    public function balanceSheet(Request $request)
    {
        $data = $this->getBalanceSheetData($request);
        return view('loan-manager.reports.balance-sheet', $data);
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

        // 5. MFI Savings (deposits/withdrawals/interest postings)
        $mfiTxs = MfiTransaction::where('loan_manager_id', $manager->id)
            ->whereIn('transaction_type', ['deposit', 'withdrawal', 'interest'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('account.client')
            ->get();
        foreach ($mfiTxs as $tx) {
            $clientName = optional(optional($tx->account)->client)->name ?? 'Unknown Client';
            $label = match ($tx->transaction_type) {
                'deposit' => 'Savings Deposit: ',
                'withdrawal' => 'Savings Withdrawal: ',
                'interest' => 'Savings Interest Posted: ',
                default => ucfirst($tx->transaction_type) . ': ',
            };
            $masterTransactionList->push((object)[
                'date' => $tx->transaction_date,
                'description' => $label . $clientName,
                'amount_out' => $tx->transaction_type === 'withdrawal' ? $tx->amount : 0,
                'amount_in' => $tx->transaction_type !== 'withdrawal' ? $tx->amount : 0,
            ]);
        }

        $transactions = $masterTransactionList->sortBy('date');

        return view('loan-manager.reports.general-ledger', compact('transactions', 'startDate', 'endDate'));
    }


    // === TRIAL BALANCE (FIXED) ===
    public function trialBalance(Request $request)
    {
        $manager = auth()->user()->loanManager;
        $manager->refresh(); 
        
        $endDate = $request->input('end_date', now()->toDateString());

        // 1. DEBITS (Assets + Expenses)
        $bsReq = new Request(['report_date' => $endDate]); 
        
        // Grab the array of data directly
        $bsData = $this->getBalanceSheetData($bsReq); 
        
        $assets = $bsData['assets'] ?? collect([]);
        $liabilities = $bsData['liabilities'] ?? collect([]);
        $equities = $bsData['equity'] ?? collect([]); // FIX: Fetch full equity including Retained Earnings
        
        $plReqDate = new Request(['end_date' => $endDate]);
        $plData = $this->getProfitAndLossData($plReqDate);
        $expenses = $plData['expenseAccounts'];
        $income = $plData['incomeAccounts'];

        $accounts = collect([]);

        // Add Assets (Dr)
        foreach($assets as $asset) {
            if(round($asset->balance, 2) != 0) {
                // FIX: Added 'group' => 'Assets'
                $accounts->push((object)['group' => 'Assets', 'name' => $asset->name, 'debit' => $asset->balance, 'credit' => 0]);
            }
        }
        
        // Add Expenses (Dr)
        foreach($expenses as $exp) {
             if(round($exp->period_total, 2) != 0) {
                // FIX: Added 'group' => 'Expenses'
                $accounts->push((object)['group' => 'Expenses', 'name' => $exp->name, 'debit' => $exp->period_total, 'credit' => 0]);
            }
        }

        // Add Liabilities (Cr)
        foreach($liabilities as $liab) {
             if(round($liab->balance, 2) != 0) {
                // FIX: Added 'group' => 'Liabilities'
                $accounts->push((object)['group' => 'Liabilities', 'name' => $liab->name, 'debit' => 0, 'credit' => $liab->balance]);
            }
        }
        
        // Add Equity (Cr)
        foreach($equities as $eq) {
            if(round($eq->balance, 2) != 0) {
                // FIX: Added 'group' => 'Equity'
                $accounts->push((object)['group' => 'Equity', 'name' => $eq->name, 'debit' => 0, 'credit' => $eq->balance]);
            }
        }

        // Add Income (Cr)
        foreach($income as $inc) {
            if(round($inc->period_total, 2) != 0) {
                // FIX: Added 'group' => 'Income'
                $accounts->push((object)['group' => 'Income', 'name' => $inc->name, 'debit' => 0, 'credit' => $inc->period_total]);
            }
        }

        $totalDebits = $accounts->sum('debit');
        $totalCredits = $accounts->sum('credit');

        return view('loan-manager.reports.trial-balance', compact('accounts', 'totalDebits', 'totalCredits'));
    }


    // === LOAN AGING ===
    public function loanAging()
    {
        $manager = Auth::user()->loanManager;
        $overdueLoans = [];
        
        // Fetch active loans
        $activeLoans = $manager->loans()
            ->whereIn('status', ['active', 'approved'])
            ->with(['client', 'payments'])
            ->get();

        foreach ($activeLoans as $loan) {
            $totalRepayable = $loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate / 100)) + ($loan->processing_fee ?? 0);
            $totalPaid = $loan->payments->sum('amount_paid');
            
            // Mathematical Arrears Calculation (Bulletproof Fallback)
            $startDate = \Carbon\Carbon::parse($loan->start_date);
            $term = max((int)$loan->term, 1);
            $freq = strtolower($loan->repayment_frequency ?? 'months');
            
            if (str_contains($freq, 'month')) {
                $endDate = $startDate->copy()->addMonths($term);
            } elseif (str_contains($freq, 'week')) {
                $endDate = $startDate->copy()->addWeeks($term);
            } else {
                $endDate = $startDate->copy()->addMonths($term); // Default
            }
            
            $totalDays = max($startDate->diffInDays($endDate), 1);
            $daysElapsed = $startDate->diffInDays(now(), false);
            
            // If the loan has officially started
            if ($daysElapsed > 0) {
                // Calculate exactly how much SHOULD have been paid by today based on time passed
                $timeRatio = min($daysElapsed / $totalDays, 1);
                $expectedPayment = $totalRepayable * $timeRatio;
                
                $arrears = $expectedPayment - $totalPaid;
                
                // If they are behind by more than 0.01
                if ($arrears > 0.01) {
                    $loan->days_missed = $daysElapsed;
                    $loan->arrears = $arrears;
                    $loan->total_balance = max(0, $totalRepayable - $totalPaid);
                    
                    $overdueLoans[] = $loan;
                }
            }
        }
        
        return view('loan-manager.reports.loan-aging', ['loans' => collect($overdueLoans)]);
    }

    // === PRINT FORMS ===
    public function showPrintForms(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;
        $clientsWithLoans = \App\Models\Client::where('loan_manager_id', $managerId)->whereHas('loans')->with('loans')->orderBy('name')->get();
        return view('loan-manager.reports.print-forms', compact('clientsWithLoans'));
    }
}