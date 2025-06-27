<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // We need this to interact with the User model
use Illuminate\Http\Request;
use App\Models\Client; // <-- Add this
use App\Models\Loan;

class AdminController extends Controller
{
    /**
     * Job #1: Show the admin dashboard and list inactive managers.
     */
     public function index()
    {
        // --- Fetch Stats ---
        $loanManagerCount = User::where('user_type', 'loan_manager')->count();
        $clientCount = Client::count();
        $totalLoans = Loan::count();
        $totalLoanedAmount = Loan::sum('principal_amount');

        // --- Fetch Manager List (this part stays the same) ---
        $loanManagers = User::where('user_type', 'loan_manager')
                            ->with('loanManager')
                            ->get()
                            ->sortBy(function($user) {
                                return $user->loanManager->is_active;
                            });

        // --- Pass all data to the view ---
        return view('admin.dashboard', [
            'loanManagers' => $loanManagers,
            'loanManagerCount' => $loanManagerCount,
            'clientCount' => $clientCount,
            'totalLoans' => $totalLoans,
            'totalLoanedAmount' => $totalLoanedAmount,
        ]);
    }

    /**
     * Job #2: Activate a specific Loan Manager's account.
     */
    public function activate(User $manager)
    {
        // This part checks if the user is a loan manager.
        if ($manager->user_type === 'loan_manager' && $manager->loanManager) {
            
            // It finds their profile, sets is_active to true, and saves the change.
            $manager->loanManager->is_active = true;
            $manager->loanManager->save();

            // Finally, it sends you back to the dashboard with a success message.
            return redirect()->route('admin.dashboard')->with('status', 'Loan Manager has been activated successfully!');
        }

        // This is a fallback in case something goes wrong.
        return redirect()->route('admin.dashboard')->with('error', 'Could not activate this user.');
    }

    public function suspend(User $manager)
    {
        // Check if the user is actually a loan manager and has a profile
        if ($manager->user_type === 'loan_manager' && $manager->loanManager) {
            
            // Set the is_active flag to false
            $manager->loanManager->is_active = false;
            $manager->loanManager->save();

            // Redirect back to the admin dashboard with a success message
            return redirect()->route('admin.dashboard')->with('status', 'Loan Manager has been suspended successfully!');
        }

        // Redirect back with an error if something went wrong
        return redirect()->route('admin.dashboard')->with('error', 'Could not suspend this user.');
    }
}