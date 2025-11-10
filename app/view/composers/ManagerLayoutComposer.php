<?php
namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ManagerLayoutComposer
{
    public function compose(View $view)
    {
        $cashOnHand = 0;
        // Ensure we only run this for logged-in loan managers
        if (Auth::check() && Auth::user()->user_type === 'loan_manager') {
            $manager = Auth::user()->loanManager;

            if ($manager) {
                $loanPayments = $manager->payments()->sum('amount_paid');
                $loansDisbursed = $manager->loans()->sum('principal_amount');
                $expenses = $manager->expenses()->sum('amount');

                // Simple calculation
                $cashOnHand = $loanPayments - ($loansDisbursed + $expenses);
            }
        }

        $view->with('cashOnHand', $cashOnHand);
    }
}