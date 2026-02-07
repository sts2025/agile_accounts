<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoanManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard with the list of managers.
     */
    public function index()
    {
        // Fetch all users who are loan managers or have a manager profile
        $managers = User::where('user_type', 'loan_manager')
                        ->orWhereHas('loanManager')
                        ->with('loanManager')
                        ->latest()
                        ->get();

        return view('admin.dashboard', compact('managers'));
    }

    /**
     * Activate a pending manager and set their settings.
     */
    public function activate(Request $request, $id)
    {
        $manager = User::findOrFail($id);
        
        if ($manager->loanManager) {
            $manager->loanManager->update([
                'is_active' => 1,
                'currency_symbol' => $request->input('currency_symbol', 'UGX'),
                'support_phone' => $request->input('support_phone'),
            ]);
        }

        return back()->with('success', 'Manager account activated successfully.');
    }

    /**
     * Suspend a manager's access.
     */
    public function suspend($id)
    {
        $manager = User::findOrFail($id);
        
        if ($manager->loanManager) {
            $manager->loanManager->update(['is_active' => 0]);
        }

        return back()->with('warning', 'Manager account has been suspended.');
    }

    /**
     * Update manager settings (Currency/Support Phone).
     */
    public function update(Request $request, $id)
    {
        $manager = User::findOrFail($id);
        
        if ($manager->loanManager) {
            $manager->loanManager->update([
                'currency_symbol' => $request->input('currency_symbol'),
                'support_phone' => $request->input('support_phone'),
                'is_active' => $request->has('is_active') ? 1 : $manager->loanManager->is_active,
            ]);
        }

        return back()->with('success', 'Manager settings updated successfully.');
    }

    /**
     * Delete a manager and ALL their associated data.
     */
    public function destroy($id)
    {
        $managerProfile = LoanManager::findOrFail($id);
        $user = $managerProfile->user;

        try {
            DB::transaction(function () use ($managerProfile, $user) {
                // 1. Get all loan IDs belonging to this manager
                $loanIds = $managerProfile->loans()->pluck('id');

                // 2. Delete children of Loans (Collaterals, Guarantors, Payments)
                DB::table('collaterals')->whereIn('loan_id', $loanIds)->delete();
                DB::table('guarantors')->whereIn('loan_id', $loanIds)->delete();
                DB::table('payments')->whereIn('loan_id', $loanIds)->delete();
                
                if (DB::getSchemaBuilder()->hasTable('repayment_schedules')) {
                    DB::table('repayment_schedules')->whereIn('loan_id', $loanIds)->delete();
                }

                // 3. Delete Loans
                $managerProfile->loans()->delete();

                // 4. Delete Clients
                $managerProfile->clients()->delete();

                // 5. Delete other associated data
                $managerProfile->expenses()->delete();
                $managerProfile->bankTransactions()->delete();
                $managerProfile->cashTransactions()->delete();

                // 6. Delete the Profile and the User
                $managerProfile->delete();
                if ($user) {
                    $user->delete();
                }
            });

            return back()->with('success', 'Manager and all associated records deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /**
     * Impersonate (Login As) Fix
     */
    public function impersonate($id)
    {
        $user = User::findOrFail($id);
        
        // Store the Admin's ID in the session before switching
        session()->put('original_admin_id', Auth::id());
        
        Auth::login($user);
        
        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonating and return to Admin Panel.
     */
    public function stopImpersonate()
    {
        $adminId = session()->get('original_admin_id');
        
        if ($adminId) {
            $admin = User::find($adminId);
            Auth::login($admin);
            session()->forget('original_admin_id');
        }

        return redirect()->route('admin.dashboard');
    }
}