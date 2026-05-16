<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoanManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard with the list of managers.
     */
    public function index()
    {
        // --- THE FIX: AUTO-SWEEP EXPIRED SUBSCRIPTIONS ---
        // Automatically suspend anyone whose subscription has expired.
        // This ensures they are locked out the moment their time is up!
        LoanManager::where('is_active', 1)
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', Carbon::now())
            ->update(['is_active' => 0]);
        // --------------------------------------------------

        // Fetch all users who are loan managers or have a manager profile
        $managers = User::where('user_type', 'loan_manager')
                        ->orWhereHas('loanManager')
                        ->with('loanManager')
                        ->latest()
                        ->get();

        return view('admin.dashboard', compact('managers'));
    }

    /**
     * Update the subscription expiry date.
     */
    public function updateSubscription(Request $request)
    {
        $request->validate([
            'manager_id' => 'required|exists:loan_managers,id',
            'duration' => 'required|string',
            'custom_date' => 'nullable|date|after:today',
        ]);

        $manager = LoanManager::findOrFail($request->manager_id);
        
        $startDate = ($manager->subscription_expires_at && Carbon::parse($manager->subscription_expires_at)->isFuture()) 
            ? Carbon::parse($manager->subscription_expires_at) 
            : Carbon::now();

        switch ($request->duration) {
            case '1_month': $newExpiry = $startDate->addMonth(); break;
            case '3_months': $newExpiry = $startDate->addMonths(3); break;
            case '6_months': $newExpiry = $startDate->addMonths(6); break;
            case '1_year': $newExpiry = $startDate->addYear(); break;
            case 'custom': $newExpiry = Carbon::parse($request->custom_date); break;
            case 'deactivate': $newExpiry = Carbon::now()->subDay(); break;
            default: return back()->with('error', 'Invalid duration selected.');
        }

        $manager->update([
            'subscription_expires_at' => $newExpiry,
            'is_active' => ($request->duration === 'deactivate') ? 0 : 1
        ]);

        return back()->with('success', "Subscription updated until " . $newExpiry->format('d M, Y'));
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
     * Suspend an active manager.
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
     * Update manager settings.
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
     * Permanently delete a manager and all their data.
     */
    public function destroy($id)
    {
        $managerProfile = LoanManager::findOrFail($id);
        $user = $managerProfile->user;

        try {
            DB::transaction(function () use ($managerProfile, $user) {
                $loanIds = $managerProfile->loans()->pluck('id');
                DB::table('collaterals')->whereIn('loan_id', $loanIds)->delete();
                DB::table('guarantors')->whereIn('loan_id', $loanIds)->delete();
                DB::table('payments')->whereIn('loan_id', $loanIds)->delete();
                if (DB::getSchemaBuilder()->hasTable('repayment_schedules')) {
                    DB::table('repayment_schedules')->whereIn('loan_id', $loanIds)->delete();
                }
                $managerProfile->loans()->delete();
                $managerProfile->clients()->delete();
                $managerProfile->expenses()->delete();
                $managerProfile->bankTransactions()->delete();
                $managerProfile->cashTransactions()->delete();
                $managerProfile->delete();
                if ($user) { $user->delete(); }
            });
            return back()->with('success', 'Manager deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /**
     * Impersonate a user account.
     */
    public function impersonate($id)
    {
        $user = User::findOrFail($id);
        session()->put('original_admin_id', Auth::id());
        Auth::login($user);
        return redirect()->route('dashboard');
    }

    /**
     * Return back to Admin account.
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