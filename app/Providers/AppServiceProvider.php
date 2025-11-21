<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Routing\Router; // <-- ADD THIS IMPORT
use App\View\Composers\ManagerLayoutComposer;

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
    }
}