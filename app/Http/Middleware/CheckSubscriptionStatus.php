<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckSubscriptionStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check only if the user is a loan manager
        if ($user && $user->user_type === 'loan_manager') {
            $manager = $user->loanManager;

            // Check if subscription_ends_at is in the past
            if ($manager && $manager->subscription_ends_at && Carbon::parse($manager->subscription_ends_at)->isPast()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')->withErrors(['email' => 'Your subscription has expired. Please contact support to renew.']);
            }
        }

        return $next($request);
    }
}