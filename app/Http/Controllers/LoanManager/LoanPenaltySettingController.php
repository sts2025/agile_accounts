<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\LoanPenaltySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanPenaltySettingController extends Controller
{
    public function edit()
    {
        $managerId = Auth::user()->loanManager->id;

        $settings = LoanPenaltySetting::firstOrCreate(
            ['loan_manager_id' => $managerId],
            ['penalty_type' => 'flat', 'penalty_amount' => 0, 'penalty_percent' => 0, 'grace_period_days' => 0]
        );

        return view('loan-manager.loans.penalty-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'penalty_type' => 'required|in:flat,percent_overdue',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_percent' => 'nullable|numeric|min:0|max:100',
            'grace_period_days' => 'required|integer|min:0',
            'bucket_days' => 'nullable|array',
            'bucket_days.*' => 'nullable|integer|min:1',
            'bucket_rates' => 'nullable|array',
            'bucket_rates.*' => 'nullable|numeric|min:0|max:100',
        ]);

        // Zip the two parallel arrays (bucket_days[i] / bucket_rates[i]) into
        // the {"30": 10, "60": 25, ...} shape stored on the model, skipping
        // any row the manager left blank.
        $provisionRates = [];
        foreach ($validated['bucket_days'] ?? [] as $index => $days) {
            $rate = $validated['bucket_rates'][$index] ?? null;
            if ($days !== null && $days !== '' && $rate !== null && $rate !== '') {
                $provisionRates[(string) $days] = (float) $rate;
            }
        }

        $settings = LoanPenaltySetting::firstOrCreate(['loan_manager_id' => $managerId]);
        $settings->update([
            'penalty_type' => $validated['penalty_type'],
            'penalty_amount' => $validated['penalty_amount'] ?? 0,
            'penalty_percent' => $validated['penalty_percent'] ?? 0,
            'grace_period_days' => $validated['grace_period_days'],
            'provision_rates' => $provisionRates ?: null,
        ]);

        return back()->with('success', 'Penalty and arrears settings updated.');
    }
}
