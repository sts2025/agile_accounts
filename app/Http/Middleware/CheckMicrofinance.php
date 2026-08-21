<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckMicrofinance
{
    /**
     * Gate access to the Microfinance (savings/MFI) module behind the
     * per-tenant `is_mfi` upgrade flag stored on the loan_managers table.
     *
     * This is the enforcement half of the "Upgrade to MFI" banner on the
     * dashboard: without this middleware, any subscribed loan manager could
     * already reach /mfi/* routes directly, upgraded or not.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admins are never blocked by tenant-level feature flags.
        if ($user->isAdmin()) {
            return $next($request);
        }

        $manager = method_exists($user, 'getCompany') ? $user->getCompany() : null;

        if (!$manager || !$manager->is_mfi) {
            return redirect()->route('dashboard')
                ->with('error', 'This feature requires the Microfinance upgrade. Upgrade your account from the dashboard to unlock Savings Accounts.');
        }

        return $next($request);
    }
}
