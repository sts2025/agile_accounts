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
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 1. ADMIN BYPASS: Admins and impersonating Admins are never locked out
        if ($user->user_type === 'admin' || session()->has('original_admin_id')) {
            return $next($request);
        }

        // 2. MANAGER & CASHIER CHECK
        if ($user->user_type === 'loan_manager' || $user->role === 'cashier') {
            
            // Get the business profile (Works for both the Manager and their Cashiers)
            $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;

            // A. Check if profile exists and is not suspended
            if (!$manager || empty($manager->currency_symbol) || $manager->is_active == 0) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->withErrors(['email' => 'Account is pending activation or has been suspended.']);
            }

            // B. Check Subscription Expiry (FIXED COLUMN NAME: subscription_expires_at)
            if ($manager->subscription_expires_at && Carbon::parse($manager->subscription_expires_at)->isPast()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->withErrors(['email' => 'Your subscription has expired. Please contact the Admin to renew.']);
            }
        }

        return $next($request);
    }
}