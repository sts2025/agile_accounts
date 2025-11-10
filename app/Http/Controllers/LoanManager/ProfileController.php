<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $manager = Auth::user()->loanManager;
        return view('loan-manager.profile.edit', compact('manager'));
    }

    public function update(Request $request)
    {
        $manager = Auth::user()->loanManager;

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logos', 'public');
            $manager->company_logo_path = $path;
        }

        $manager->company_name = $validated['company_name'] ?? $manager->company_name;
        $manager->company_phone = $validated['company_phone'] ?? $manager->company_phone;
        $manager->save();

        return redirect()->route('profile.edit')->with('status', 'Profile updated successfully!');
    }
}