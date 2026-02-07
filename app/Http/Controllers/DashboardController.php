<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\CashTransaction;
use App\Models\BankTransaction;
use App\Models\ExpenseCategory; 
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard for the Loan Manager.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // FIX: Use the new helper to get the company profile (Works for Managers AND Cashiers)
        // This relies on the getCompany() method in your User model.
        $manager = $user->getCompany();

        // Safety check: If no company found (e.g. ghost user), kick them out
        if (!$manager) {
             Auth::logout();
             return redirect('/login')->with('error', 'No company profile linked to this account.');
        }

        $managerId = $manager->id;
        $currency = $manager->currency_symbol ?? 'UGX';

        // --- 1. Stats Cards Data (Top Widgets) ---
        $totalClients = $manager->clients()->count();
        $activeLoansCount = $manager->loans()->where('status', 'active')->count();
        $totalLoanAmount = $manager->loans()->sum('principal_amount');

        // --- 2. DATA FOR MODALS (Clients & Expense Categories) ---
        $allClientsWithLoans = Client::where('loan_manager_id', $managerId)
            ->whereHas('loans', fn($query) => $query->where('status', 'active'))
            ->with(['loans' => fn($query) => $query->where('status', 'active')])
            ->get();

        $expenseCategories = ExpenseCategory::all();

        // --- 3. Daily Transactions & Cash Flow ---
        $reportDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        // A. OPENING BALANCE
        // Calculates cash position strictly BEFORE the selected date starts.
        // Formula includes the initial Opening Balance from Settings.
        $openingBalance = $this->calculateCashBalance($manager, $reportDate->copy()->startOfDay(), true);

        // B. DAILY MOVEMENTS (Display only)
        $loansGivenToday = $manager->loans()->whereDate('start_date', $reportDate)->get();
        $paymentsReceivedToday = $manager->payments()->whereDate('payment_date', $reportDate)->get();
        
        $totalLoanGiven = $loansGivenToday->sum('principal_amount');
        $totalPaidCash = $paymentsReceivedToday->sum('amount_paid');

        // C. CLOSING STOCK
        // Calculates cash position at the END of the selected date.
        $closingStock = $this->calculateCashBalance($manager, $reportDate->copy()->endOfDay(), false);

        // --- 4. Chart Data (Last 30 Days) ---
        $labels = []; $loanData = []; $paymentData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            $loanData[] = $manager->loans()->whereDate('start_date', $date)->sum('principal_amount');
            $paymentData[] = $manager->payments()->whereDate('payment_date', $date)->sum('amount_paid');
        }
        
        return view('loan-manager.dashboard', [
            'totalClients' => $totalClients,
            'activeLoansCount' => $activeLoansCount,
            'totalLoanedAmount' => $totalLoanAmount,
            'allClientsWithLoans' => $allClientsWithLoans,
            'expenseCategories' => $expenseCategories,
            'reportDate' => $reportDate,
            'totalLoanGiven' => $totalLoanGiven,
            'totalPaidCash' => $totalPaidCash,
            'openingBalance' => $openingBalance,
            'closingStock' => $closingStock, 
            'chartData' => ['labels' => $labels, 'loans' => $loanData, 'payments' => $paymentData],
            'currency' => $currency
        ]);
    }

    /**
     * MASTER CALCULATION FOR CASH ON HAND
     * Formula: Opening Balance (from settings) + (All Inflows) - (All Outflows)
     */
    private function calculateCashBalance($manager, $date, $before = false)
    {
        $managerId = $manager->id;
        $operator = $before ? '<' : '<=';

        // --- 1. START WITH THE GLOBAL OPENING BALANCE FROM SETTINGS ---
        $initialBalance = $manager->opening_balance ?? 0;

        // --- 2. MONEY COMING IN (+) ---
        $payments = Payment::whereHas('loan', function($q) use ($managerId) {
            $q->where('loan_manager_id', $managerId);
        })->where('payment_date', $operator, $date)->sum('amount_paid');

        $otherInflows = CashTransaction::where('loan_manager_id', $managerId)
            ->whereIn('type', ['inflow', 'Inflow', 'INFLOW'])
            ->where('transaction_date', $operator, $date)
            ->sum('amount');

        $bankWithdrawals = BankTransaction::where('loan_manager_id', $managerId)
            ->whereIn('type', ['Withdrawal', 'withdrawal', 'WITHDRAWAL'])
            ->where('deposit_date', $operator, $date)
            ->sum('amount');

        // --- 3. MONEY GOING OUT (-) ---
        $loansGiven = Loan::where('loan_manager_id', $managerId)
            ->where('start_date', $operator, $date)
            ->sum('principal_amount');

        $expenses = Expense::where('loan_manager_id', $managerId)
            ->where('expense_date', $operator, $date)
            ->sum('amount');

        $otherOutflows = CashTransaction::where('loan_manager_id', $managerId)
            ->whereIn('type', ['outflow', 'Outflow', 'OUTFLOW'])
            ->where('transaction_date', $operator, $date)
            ->sum('amount');

        $bankDeposits = BankTransaction::where('loan_manager_id', $managerId)
            ->whereIn('type', ['Deposit', 'deposit', 'DEPOSIT'])
            ->where('deposit_date', $operator, $date)
            ->sum('amount');

        // FINAL FORMULA: Initial + Inflows - Outflows
        return $initialBalance + ($payments + $otherInflows + $bankWithdrawals) - ($loansGiven + $expenses + $otherOutflows + $bankDeposits);
    }
}