<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;

class ManagerLayoutComposer
{
    public function compose(View $view): void
    {
        $cashOnHand = $this->calculateCashOnHand();
        $view->with('cashOnHand', $cashOnHand);
    }

    protected function calculateCashOnHand(): float
    {
        if (!Auth::check() || Auth::user()->user_type !== 'loan_manager') {
            return 0;
        }

        $cashAccount = Account::where('name', 'Cash on Hand')->first();
        if (!$cashAccount) {
            return 0;
        }

        $managerId = Auth::id();
        $debits = $cashAccount->generalLedgerTransactions()
            ->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))
            ->sum('debit');

        $credits = $cashAccount->generalLedgerTransactions()
            ->whereHas('loan', fn($q) => $q->where('loan_manager_id', $managerId))
            ->sum('credit');

        return $debits - $credits;
    }
}