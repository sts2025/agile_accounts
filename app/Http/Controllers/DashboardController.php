<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Account;
use App\Models\LoanManager;
use Illuminate\Http\Request;
use App\Models\ExpenseCategory; // This is already here, which is great
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // <-- Make sure this is imported

class DashboardController extends Controller
{
    /**
     * Show the application dashboard for the Loan Manager.
     */
    public function index(Request $request)
    {
        $manager = Auth::user()->loanManager;
        if (!$manager) {
             Auth::logout();
             return redirect('/login')->with('error', 'Loan manager profile not found.');
        }
        $managerId = $manager->id;

        // --- 1. Stats Cards Data ---
        $totalClients = $manager->clients()->count();
        $activeLoansQuery = $manager->loans()->where('status', 'active');
        $activeLoansCount = $activeLoansQuery->count();
        $totalLoanAmount = $manager->loans()->sum('principal_amount');
        
        // ===================================================
        // --- 2. DATA FOR THE MODAL (THIS IS THE FIX) ---
        // This provides the $allClientsWithLoans variable for your new "Record Payment" modal
        // It gets all clients who have at least one active loan, and loads ONLY their active loans.
        // ===================================================
        $allClientsWithLoans = Client::where('loan_manager_id', $managerId)
                                     ->whereHas('loans', fn($query) => $query->where('status', 'active')) // Only clients with active loans
                                     ->with(['loans' => fn($query) => $query->where('status', 'active')]) // Only load active loans
                                     ->get();

        // *** THIS IS THE NEW LINE FOR THE EXPENSE MODAL ***
        $expenseCategories = ExpenseCategory::all();


        // --- 3. Daily Transactions Table Data ---
        $reportDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $loansGivenToday = $manager->loans()->whereDate('start_date', $reportDate)->get();
        $paymentsReceivedToday = $manager->payments()->whereDate('payment_date', $reportDate)->get();
        $totalLoanGiven = $loansGivenToday->sum('principal_amount');
        $totalPaidCash = $paymentsReceivedToday->sum('amount_paid');

        // --- 4. Cash at Hand ---
        $cashAccount = Account::firstOrCreate(['name' => 'Cash on Hand']);
        $openingBalance = $cashAccount ? $cashAccount->getOpeningBalance($reportDate, $managerId) : 0;
        $closingStock = $openingBalance + $totalPaidCash - $totalLoanGiven;
        
        // --- 5. Chart Data (Last 30 Days) ---
        $labels = [];
        $loanData = [];
        $paymentData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            $loanData[] = $manager->loans()->whereDate('start_date', $date)->sum('principal_amount');
            $paymentData[] = $manager->payments()->whereDate('payment_date', $date)->sum('amount_paid');
        }
        $chartData = [
            'labels' => $labels,
            'loans' => $loanData,
            'payments' => $paymentData,
        ];

        // --- 6. Pass ALL data to the view ---
        return view('loan-manager.dashboard', [
            'totalClients' => $totalClients,
            'activeLoansCount' => $activeLoansCount,
            'totalLoanedAmount' => $totalLoanAmount,
            
            // ===================================================
            // This is the new variable for the modal
            // ===================================================
            'allClientsWithLoans' => $allClientsWithLoans,

            // *** THIS IS THE NEW LINE FOR THE EXPENSE MODAL ***
            'expenseCategories' => $expenseCategories,
            
            'reportDate' => $reportDate,
            'totalLoanGiven' => $totalLoanGiven,
            'totalPaidCash' => $totalPaidCash,
            'openingBalance' => $openingBalance,
            'closingStock' => $closingStock, 
            'chartData' => $chartData,
        ]);
    }
}

