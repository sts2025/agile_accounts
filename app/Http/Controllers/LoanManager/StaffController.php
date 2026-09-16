<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function index()
    {
        // Use getCompany() if available, otherwise fallback to relationship
        // This ensures consistent behavior if you change how relationships work later
        $user = Auth::user();
        $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;

        // Fetch users where role is cashier and they belong to this manager's business
        $staff = User::where('loan_manager_id', $manager->id)
                     ->where('role', 'cashier')
                     ->with('branch')
                     ->get();

        $branches = Branch::where('loan_manager_id', $manager->id)->where('is_active', true)->orderBy('name')->get();

        return view('loan-manager.staff.index', compact('staff', 'branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'branch_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('branches', 'id')->where('loan_manager_id', $manager->id)],
        ]);

        try {
            DB::beginTransaction();

            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'cashier',
                'user_type' => 'loan_manager', // FIX: Added this line to satisfy DB constraint
                'loan_manager_id' => $manager->id, // Link to the current business
                'branch_id' => $validated['branch_id'] ?? null,
            ]);

            DB::commit();
            return back()->with('success', 'Cashier account created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Could not create account: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;
        
        $staffMember = User::where('id', $id)->where('loan_manager_id', $manager->id)->firstOrFail();
        $staffMember->delete();

        return back()->with('success', 'Staff member removed successfully.');
    }
}