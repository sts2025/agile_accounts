<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the loan manager's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $manager = $user->loanManager;
        return view('loan-manager.profile.edit', compact('user', 'manager'));
    }

    /**
     * Update the manager's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $manager = $user->loanManager;

        // --- Validation ---
        $validated = $request->validate([
            // Personal User Details
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|confirmed|min:8',
            
            // Business Details (For Receipt)
            'company_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20', 
            'address' => 'required|string|max:255',      
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            
            // Financials
            'opening_balance' => 'nullable|numeric|min:0', // NEW FIELD Added here
        ]);

        // --- 1. Handle Logo Upload ---
        if ($request->hasFile('company_logo')) {
            // Delete old logo if exists
            if ($manager->company_logo_path && Storage::disk('public')->exists($manager->company_logo_path)) {
                Storage::disk('public')->delete($manager->company_logo_path); 
            }
            // Store new logo
            $path = $request->file('company_logo')->store('logos', 'public');
            $manager->company_logo_path = $path;
        }

        // --- 2. Update User Record (Login Info) ---
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        $user->update($userData);

        // --- 3. Update LoanManager Record (Receipt Info & Financials) ---
        if ($manager) {
            $manager->company_name = $validated['company_name'];
            $manager->phone_number = $validated['phone_number']; 
            $manager->address = $validated['address']; 
            
            // Save Opening Balance
            $manager->opening_balance = $request->input('opening_balance', 0);
            
            // Preserve your currency symbol logic
            if ($request->has('currency_symbol')) {
                $manager->currency_symbol = $request->input('currency_symbol');
            }
            
            $manager->save();
        }

        return back()->with('success', 'Profile and Financial details updated successfully!');
    }
}