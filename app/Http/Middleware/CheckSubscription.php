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
        if ($user->isAdmin() || session()->has('original_admin_id')) {
            return $next($request);
        }

        // 2. MANAGER & CASHIER CHECK
        if ($user->user_type === 'loan_manager' || $user->role === 'cashier') {
            
            // Get the business profile (Works for both the Manager and their Cashiers)
            $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;

            if ($manager) {
                // THE GOLDEN TICKET: Force fetch fresh data from database!
                // This stops Laravel from using cached data to sneak expired users through.
                $manager->refresh();

                // A. Check Subscription Expiry FIRST
                if ($manager->subscription_expires_at && Carbon::parse($manager->subscription_expires_at)->isPast()) {
                    
                    // HARD KILL SWITCH: Turn off the active status in the database permanently
                    $manager->is_active = 0;
                    $manager->save();

                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect('/login')->withErrors(['email' => 'Your subscription has expired. Please contact BKR TECH( 0740859082) to renew.']);
                }

                // B. Check if profile is suspended or pending
                if (empty($manager->currency_symbol) || $manager->is_active == 0) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect('/login')->withErrors(['email' => 'Account is pending activation or has been suspended.']);
                }

            } else {
                Auth::logout();
                return redirect('/login')->withErrors(['email' => 'Account profile error. Contact Admin.']);
            }
        }

        return $next($request);
    }
}