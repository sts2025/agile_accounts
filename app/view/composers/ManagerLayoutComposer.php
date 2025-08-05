<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;

class ManagerLayoutComposer
{
    public function compose(View $view)
    {
        $cashOnHand = 0;
        if (Auth::check() && Auth::user()->user_type === 'loan_manager') {
            $managerId = Auth::id();
            $cashAccount = Account::where('name', 'Cash on Hand')->first();

            if ($cashAccount) {
                $debits = $cashAccount->generalLedgerTransactions()
                    ->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))
                    ->sum('debit');

                $credits = $cashAccount->generalLedgerTransactions()
                    ->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))
                    ->sum('credit');

                $cashOnHand = $debits - $credits;
            }
        }
        $view->with('cashOnHand', $cashOnHand);
    }
}