<?php

namespace App\View\Composers; // *** CORRECTED: Use standard capitalization ***

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\BroadcastMessage; // *** ADDED: Import for BroadcastMessage ***
use App\Models\LoanManager;

class ManagerLayoutComposer
{
    public function compose(View $view)
    {
        // Initialize variables that might be needed elsewhere
        $cashOnHand = 0;
        $loanManager = null;
        $broadcastMessage = null; // *** ADDED: Initialize broadcast message ***

        // Ensure we only run this for logged-in loan managers
        if (Auth::check() && Auth::user()->user_type === 'loan_manager') {
            
            // *** FIX 1: Use load('loanManager') to prevent N+1 query problem ***
            $loanManager = Auth::user()->load('loanManager')->loanManager;
            
            // *** FIX 2: Fetch the active message ***
            $broadcastMessage = BroadcastMessage::active()->latest()->first();


            if ($loanManager) {
                $loanPayments = $loanManager->payments()->sum('amount_paid');
                $loansDisbursed = $loanManager->loans()->sum('principal_amount');
                $expenses = $loanManager->expenses()->sum('amount');

                // Simple calculation
                $cashOnHand = $loanPayments - ($loansDisbursed + $expenses);
            }
        }

        $view->with('cashOnHand', $cashOnHand);
        $view->with('broadcastMessage', $broadcastMessage); // *** ADDED: Pass message to all manager views ***
    }
}