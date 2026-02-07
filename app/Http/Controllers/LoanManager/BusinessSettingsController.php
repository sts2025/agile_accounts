<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BusinessSettingsController extends Controller
{
    /**
     * Show the form for editing business settings.
     */
    public function edit()
    {
        $manager = Auth::user()->loanManager;
        return view('loan-manager.settings.edit', compact('manager'));
    }

    /**
     * Update business settings including Opening Balance.
     */
    public function update(Request $request)
    {
        $manager = Auth::user()->loanManager;

        $validated = $request->validate([
            'company_name'    => 'required|string|max:255',
            'company_phone'   => 'nullable|string|max:20',
            'company_email'   => 'nullable|email|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_logo'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        // Handle Logo Upload
        if ($request->hasFile('company_logo')) {
            // Delete old logo if it exists
            if ($manager->company_logo) {
                Storage::disk('public')->delete($manager->company_logo);
            }
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $manager->company_logo = $path;
        }

        // Update Manager Details
        $manager->company_name = $validated['company_name'];
        $manager->company_phone = $validated['company_phone'];
        $manager->company_email = $validated['company_email'];
        $manager->company_address = $validated['company_address'];
        
        // Save the Opening Balance
        $manager->opening_balance = $validated['opening_balance'] ?? 0;
        
        $manager->save();

        return back()->with('success', 'Business settings and Opening Balance updated successfully!');
    }
}