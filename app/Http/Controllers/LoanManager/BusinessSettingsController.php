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
            'sms_provider'    => 'nullable|string|in:africastalking,twilio,log',
            'sms_api_key'     => 'nullable|string|max:255',
            'sms_api_secret'  => 'nullable|string|max:255',
            'sms_sender_id'   => 'nullable|string|max:30',
            'sms_username'    => 'nullable|string|max:100',
        ]);

        // Handle Logo Upload
        // NOTE: the real DB/model column is company_logo_path — the form
        // field is named company_logo (matching the upload input), but
        // that must never be confused with the storage attribute. This
        // was previously writing to a nonexistent 'company_logo' column,
        // which is why logos never actually saved or appeared on prints.
        if ($request->hasFile('company_logo')) {
            // Delete old logo if it exists
            if ($manager->company_logo_path) {
                Storage::disk('public')->delete($manager->company_logo_path);
            }
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $manager->company_logo_path = $path;
        }

        // Update Manager Details
        $manager->company_name = $validated['company_name'];
        $manager->company_phone = $validated['company_phone'];
        $manager->company_email = $validated['company_email'];
        $manager->company_address = $validated['company_address'];
        
        // Save the Opening Balance
        $manager->opening_balance = $validated['opening_balance'] ?? 0;

        // SMS gateway (own account per tenant — see SmsService)
        $manager->sms_provider = $validated['sms_provider'] ?? null;
        $manager->sms_api_key = $validated['sms_api_key'] ?? null;
        $manager->sms_api_secret = $validated['sms_api_secret'] ?? null;
        $manager->sms_sender_id = $validated['sms_sender_id'] ?? null;
        $manager->sms_username = $validated['sms_username'] ?? null;

        $manager->save();

        return back()->with('success', 'Business settings and Opening Balance updated successfully!');
    }
}