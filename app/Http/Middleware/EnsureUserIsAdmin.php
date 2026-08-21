<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Gate the entire /admin/* route group behind User::isAdmin().
     *
     * Without this, every route in that group (manager activation/
     * suspension/deletion, subscription control, broadcast messages, and
     * critically the "impersonate any user by ID" endpoint) was reachable
     * by ANY authenticated user — including ordinary loan-manager tenants
     * and their cashier staff — since the group was only wrapped in
     * ['auth']. User::isAdmin() already existed as the intended gatekeeper
     * but nothing was calling it.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
