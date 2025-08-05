<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Account;
use App\Models\BroadcastMessage;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $managerId = Auth::id();
        $today = \Carbon\Carbon::today();

        // Get loans given out today
        $loansGiven = \App\Models\Loan::where('loan_manager_id', $managerId)->whereDate('start_date', $today)->get();
        // Get payments received today
        $paymentsReceived = \App\Models\Payment::whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->whereDate('payment_date', $today)->get();
        
        $totalLoanGiven = $loansGiven->sum('principal_amount');
        $totalPaidCash = $paymentsReceived->where('payment_method', 'Cash')->sum('amount_paid');
        
        $cashOnHand = 0;
        $openingBalance = 0;
        $closingStock = 0;
        $cashAccount = \App\Models\Account::where('name', 'Cash on Hand')->first();
        if ($cashAccount) {
            $allDebits = $cashAccount->generalLedgerTransactions()->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->sum('debit');
            $allCredits = $cashAccount->generalLedgerTransactions()->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))->sum('credit');
            $cashOnHand = $allDebits - $allCredits;
            
            // Calculate opening balance (cash at hand at the start of today)
            $openingBalance = $cashOnHand - $totalPaidCash + $totalLoanGiven;
            // CORRECTED LINE:
            $closingStock = $openingBalance + $totalPaidCash - $totalLoanGiven;
        }

        $latestMessage = \App\Models\BroadcastMessage::latest()->first();

        return view('loan-manager.dashboard', [
            'reportDate' => $today,
            'loansGiven' => $loansGiven,
            'paymentsReceived' => $paymentsReceived,
            'openingBalance' => $openingBalance,
            'totalLoanGiven' => $totalLoanGiven,
            'totalPaidCash' => $totalPaidCash,
            'closingStock' => $closingStock,
            'latestMessage' => $latestMessage,
            'cashOnHand' => $cashOnHand,
        ]);
    }
}