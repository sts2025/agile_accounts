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
        $loanManager = Auth::user();
        $clientCount = $loanManager->clients()->count();
        $activeLoans = $loanManager->loans()->where('status', 'active')->get();
        $activeLoanCount = $activeLoans->count();
        $totalLoanedAmount = $activeLoans->sum('principal_amount');
        $latestMessage = \App\Models\BroadcastMessage::latest()->first();

        return view('dashboard', [
            'clientCount' => $clientCount,
            'activeLoanCount' => $activeLoanCount,
            'totalLoanedAmount' => $totalLoanedAmount,
            'latestMessage' => $latestMessage, // This line had a typo
        ]);
    }
}
        