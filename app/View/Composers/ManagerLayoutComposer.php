<?php

namespace App\View\Composers; // *** CORRECTED: Use standard capitalization ***

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\BroadcastMessage; // *** ADDED: Import for BroadcastMessage ***
use App\Models\LoanManager;
use App\Models\AppNotification;

class ManagerLayoutComposer
{
    public function compose(View $view)
    {
        // Initialize variables that might be needed elsewhere
        $cashOnHand = 0;
        $loanManager = null;
        $broadcastMessage = null; // *** ADDED: Initialize broadcast message ***
        $unreadNotificationCount = 0;
        $recentNotifications = collect();

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

        // Notification bell — covers both owner and cashier logins, since
        // app_notifications is addressed per-user, not per-tenant. This
        // composer runs on every manager-layout page load, so a missing
        // table (e.g. migration not yet deployed) must never take the
        // whole app down — fail quiet, same as ActivityLogObserver.
        if (Auth::check()) {
            try {
                $unreadNotificationCount = AppNotification::where('user_id', Auth::id())->unread()->count();
                $recentNotifications = AppNotification::where('user_id', Auth::id())
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get();
            } catch (\Throwable $e) {
                // table not migrated yet, or other transient issue — bell just shows empty
            }
        }

        $view->with('cashOnHand', $cashOnHand);
        $view->with('broadcastMessage', $broadcastMessage); // *** ADDED: Pass message to all manager views ***
        $view->with('unreadNotificationCount', $unreadNotificationCount);
        $view->with('recentNotifications', $recentNotifications);
    }
}