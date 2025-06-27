<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User; // Import the User model

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define the 'isSuperAdmin' gate
        Gate::define('isSuperAdmin', function (User $user) {
            return $user->user_type === 'super_admin';
        });

        // Define the 'isLoanManager' gate
        Gate::define('isLoanManager', function (User $user) {
            // Ensure loan manager is active to access loan manager features
            return $user->user_type === 'loan_manager' && $user->loanManager && $user->loanManager->is_active;
        });
    }
}