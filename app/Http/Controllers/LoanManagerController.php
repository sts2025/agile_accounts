<?php

namespace App\Http\Controllers;

use App\Models\LoanManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon; // For date manipulation

class LoanManagerController extends Controller
{
    /**
     * Display a listing of all loan managers (Super Admin only).
     */
    public function index()
    {
        // Eager load the related User model
        $loanManagers = LoanManager::with('user')->get();
        return response()->json($loanManagers);
    }

    /**
     * Store a newly created loan manager in storage (Super Admin only - alternative to public register).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => 'loan_manager',
        ]);

        $loanManager = LoanManager::create([
            'user_id' => $user->id,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'is_active' => true, // Super Admin created, so active by default
            'subscription_ends_at' => Carbon::now()->addMonth(), // Example: 1 month subscription
        ]);

        return response()->json([
            'message' => 'Loan Manager created successfully.',
            'loan_manager' => $loanManager->load('user') // Load user data to return
        ], 201);
    }

    /**
     * Display the specified loan manager (Super Admin only).
     */
    public function show(LoanManager $loanManager)
    {
        return response()->json($loanManager->load('user'));
    }

    /**
     * Update the specified loan manager in storage (Super Admin only).
     */
    public function update(Request $request, LoanManager $loanManager)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $loanManager->user_id,
            'phone_number' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|required|boolean',
            'subscription_ends_at' => 'sometimes|nullable|date',
        ]);

        // Update User details
        $user = $loanManager->user;
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        $user->save();

        // Update LoanManager details
        $loanManager->update($request->only([
            'phone_number',
            'address',
            'is_active',
            'subscription_ends_at',
        ]));

        return response()->json([
            'message' => 'Loan Manager updated successfully.',
            'loan_manager' => $loanManager->load('user')
        ]);
    }

    /**
     * Remove the specified loan manager from storage (Super Admin only).
     */
    public function destroy(LoanManager $loanManager)
    {
        // First delete the associated User record
        $loanManager->user()->delete();
        $loanManager->delete();

        return response()->json(['message' => 'Loan Manager deleted successfully.']);
    }

    /**
     * Activate a loan manager (Super Admin only).
     */
    public function activate(LoanManager $loanManager)
    {
        $loanManager->is_active = true;
        // Optionally, set subscription_ends_at here if it's the first activation or renewal
        // $loanManager->subscription_ends_at = Carbon::now()->addMonth(); // Example: 1 month subscription from activation
        $loanManager->save();
        return response()->json(['message' => 'Loan Manager activated successfully.', 'loan_manager' => $loanManager->load('user')]);
    }

    /**
     * Deactivate a loan manager (Super Admin only).
     */
    public function deactivate(LoanManager $loanManager)
    {
        $loanManager->is_active = false;
        $loanManager->save();
        return response()->json(['message' => 'Loan Manager deactivated successfully.', 'loan_manager' => $loanManager->load('user')]);
    }

    /**
     * Get the subscription status for the authenticated loan manager.
     */
    public function mySubscriptionStatus(Request $request)
    {
        $user = $request->user();
        if ($user->user_type !== 'loan_manager') {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $loanManager = $user->loanManager;

        if (!$loanManager) {
            return response()->json(['message' => 'Loan Manager profile not found.'], 404);
        }

        return response()->json([
            'is_active' => $loanManager->is_active,
            'subscription_ends_at' => $loanManager->subscription_ends_at,
            'days_remaining' => $loanManager->subscription_ends_at ? Carbon::now()->diffInDays($loanManager->subscription_ends_at, false) : null, // `false` to get negative for past dates
            'message' => $loanManager->is_active ? 'Your subscription is active.' : 'Your subscription is inactive. Please contact admin on 0740859082.',
        ]);
    }
}