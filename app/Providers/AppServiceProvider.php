<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Routing\Router; // <-- ADD THIS IMPORT
use App\View\Composers\ManagerLayoutComposer;
use App\Models\Loan;
use App\Models\Client;
use App\Models\Payment;
use App\Models\JournalEntry;
use App\Observers\ActivityLogObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * We inject the Router dependency here to register middleware.
     */
    public function boot(Router $router): void // <-- Router injected here
    {
        // --- Existing View Composer Logic ---
        // This line tells Laravel to run your composer file
        // for any view that uses 'layouts.manager',
        // which will provide the $cashOnHand variable to all pages.
        View::composer('layouts.manager', ManagerLayoutComposer::class);

        // --- NEW: Middleware Registration ---
        // Since Kernel.php is missing, we register the middleware alias here.
        // This makes 'elevated_privileges' available in your controllers and routes.
        $router->aliasMiddleware(
            'elevated_privileges',
            \App\Http\Middleware\CheckElevatedPrivileges::class
        );

        // --- Audit trail ---
        // Deliberately a small, high-value set of models rather than every
        // model in the app — see ActivityLogObserver's class docblock for
        // why. Registering an observer never throws even if one of these
        // classes is later removed/renamed, but keep this list in sync
        // with ActivityLogObserver::describe()'s match arms.
        Loan::observe(ActivityLogObserver::class);
        Client::observe(ActivityLogObserver::class);
        Payment::observe(ActivityLogObserver::class);
        JournalEntry::observe(ActivityLogObserver::class);
    }
}