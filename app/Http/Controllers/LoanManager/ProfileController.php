<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // Required for email validation
use Illuminate\Support\Facades\Storage; // Required for deleting old logo

class ProfileController extends Controller
{
    /**
     * Show the form for editing the loan manager's profile.
     * The LoanManager model is accessed via the User model's relationship.
     */
    public function edit()
    {
        $manager = Auth::user()->loanManager;
        return view('loan-manager.profile.edit', compact('manager'));
    }

    /**
     * Update the manager's profile information (User and LoanManager tables).
     */
    public function update(Request $request)
    {
        $user = Auth::user(); // Get the authenticated User model instance
        $manager = $user->loanManager; // Get the related LoanManager model instance

        // --- Validation for all required fields ---
        $validated = $request->validate([
            // User Fields (Mandatory, updated from the Blade file)
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string|max:255',

            // Loan Manager Fields
            'company_name' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'currency_symbol' => 'required|string|max:10', // Field assumed from the Blade template
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // --- Handle Logo Upload and Deletion ---
        if ($request->hasFile('company_logo')) {
            // 1. Delete the old logo if a path exists
            if ($manager->company_logo_path) {
                // Check if file exists before attempting to delete
                if (Storage::disk('public')->exists($manager->company_logo_path)) {
                    Storage::disk('public')->delete($manager->company_logo_path); 
                }
            }
            
            // 2. Store the new logo and update the path in the manager record
            $path = $request->file('company_logo')->store('logos', 'public');
            $manager->company_logo_path = $path;
        }

        // --- Update Records ---
        
        // Update the core User record
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'address' => $validated['address'],
        ]);

        // Update the LoanManager record
        $manager->company_name = $validated['company_name'] ?? $manager->company_name;
        $manager->company_phone = $validated['company_phone'] ?? $manager->company_phone;
        $manager->currency_symbol = $validated['currency_symbol']; 
        $manager->save();

        return redirect()->route('profile.edit')->with('status', 'Profile updated successfully!');
    }
}