<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // <-- ADD THIS LINE
use App\View\Composers\ManagerLayoutComposer; // <-- ADD THIS LINE

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
     */
    public function boot(): void
    {
        // This line tells Laravel to run your composer file
        // for any view that uses 'layouts.manager',
        // which will provide the $cashOnHand variable to all pages.
        View::composer('layouts.manager', ManagerLayoutComposer::class);
    }
}