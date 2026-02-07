<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Ensure user is logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. BYPASS: Always allow Admin / Super Admin (ID 1)
        // OR if an Admin is currently impersonating this user
        if ($user->id === 1 || $user->user_type === 'admin' || session()->has('original_admin_id')) {
            return $next($request);
        }

        // 3. Loan Manager Checks
        if ($user->user_type === 'loan_manager') {
            $manager = $user->loanManager;

            // CHECK A: Is the account ACTIVATED? (Currency set by Admin)
            if (!$manager || empty($manager->currency_symbol)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->withErrors(['email' => 'Account pending Admin activation. Please contact support.']);
            }

            // CHECK B: Is Subscription EXPIRED?
            if ($manager->subscription_ends_at && Carbon::parse($manager->subscription_ends_at)->isPast()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->withErrors(['email' => 'Your subscription has expired. Please renew.']);
            }
        }

        return $next($request);
    }
}