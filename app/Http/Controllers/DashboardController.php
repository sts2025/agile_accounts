<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard for the Loan Manager.
     */
    public function index()
    {
        // Get the currently authenticated user (the Loan Manager)
        $loanManager = Auth::user();

        // Fetch the stats using the relationships we defined
        $clientCount = $loanManager->clients()->count();
        
        $activeLoans = $loanManager->loans()->where('status', 'active')->get();
        $activeLoanCount = $activeLoans->count();
        $totalLoanedAmount = $activeLoans->sum('principal_amount');

        // Pass the collected stats to the dashboard view
        return view('dashboard', [
            'clientCount' => $clientCount,
            'activeLoanCount' => $activeLoanCount,
            'totalLoanedAmount' => $totalLoanedAmount,
        ]);
    }
}