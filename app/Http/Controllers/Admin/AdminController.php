<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoanManager; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    /**
     * Display the Admin dashboard.
     */
    public function index()
    {
        $managers = User::where('user_type', 'loan_manager')->with('loanManager')->get();
        $totalLoanedAmount = \App\Models\Loan::sum('principal_amount') ?? 0;
        $loanManagerCount = $managers->count();
        $clientCount = \App\Models\Client::count() ?? 0;
        $totalLoans = \App\Models\Loan::count() ?? 0;
        
        return view('admin.dashboard', [
            'managers' => $managers,
            'totalLoanedAmount' => $totalLoanedAmount,
            'loanManagerCount' => $loanManagerCount,
            'clientCount' => $clientCount,
            'totalLoans' => $totalLoans,
        ]);
    }

    /**
     * Update settings for a loan manager (Activate/Suspend, Currency, Phone).
     */
    public function updateSettings(User $manager, Request $request)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'currency_symbol' => 'required|string|in:UGX,RWF',
            'support_phone' => 'required|string|max:20',
        ]);

        $loanManagerProfile = $manager->loanManager;
        if (!$loanManagerProfile) {
            return back()->with('error', 'Loan Manager profile not found for this user.');
        }

        $loanManagerProfile->update($validated);

        $status = $validated['is_active'] ? 'activated' : 'suspended';
        return back()->with('status', 'Manager ' . $manager->name . ' has been ' . $status . ' and settings saved.');
    }

    /**
     * ✅ Activate a loan manager.
     */
    public function activate($managerId)
    {
        $manager = User::findOrFail($managerId);

        // Check if it’s a loan manager
        if ($manager->user_type !== 'loan_manager') {
            return back()->with('error', 'You can only activate loan managers.');
        }

        $manager->is_active = true;
        $manager->save();

        if ($manager->loanManager) {
            $manager->loanManager->update(['is_active' => true]);
        }

        return back()->with('success', 'Manager ' . $manager->name . ' activated successfully.');
    }

    /**
     * ✅ Suspend a loan manager.
     */
    public function suspend($managerId)
    {
        $manager = User::findOrFail($managerId);

        if ($manager->user_type !== 'loan_manager') {
            return back()->with('error', 'You can only suspend loan managers.');
        }

        $manager->is_active = false;
        $manager->save();

        if ($manager->loanManager) {
            $manager->loanManager->update(['is_active' => false]);
        }

        return back()->with('success', 'Manager ' . $manager->name . ' suspended successfully.');
    }

    /**
     * Impersonate (Login As) a loan manager.
     */
    public function impersonate(User $manager)
    {
        // Security Check: Admin can't impersonate another Admin
        if ($manager->user_type === 'admin') {
             return back()->with('error', 'Cannot impersonate another admin.');
        }
        
        Session::put('original_admin_id', Auth::id());
        Auth::login($manager);
        return redirect()->route('dashboard')->with('status', 'Logged in as ' . $manager->name);
    }

    /**
     * Stop impersonating.
     */
    public function stopImpersonate()
    {
        $originalAdminId = Session::pull('original_admin_id');
        if (!$originalAdminId) {
            return redirect()->route('login');
        }
        $originalAdmin = User::find($originalAdminId);
        if ($originalAdmin) {
            Auth::login($originalAdmin);
            return redirect()->route('admin.dashboard')->with('status', 'Logged back in as Admin.');
        }
        return redirect()->route('login');
    }
}
