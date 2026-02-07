<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanManager;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Update the subscription expiry date for a loan manager.
     */
    public function update(Request $request)
    {
        $request->validate([
            'manager_id' => 'required|exists:loan_managers,id',
            'duration' => 'required|string', // '1_month', '3_months', '1_year', 'custom'
            'custom_date' => 'nullable|date_format:Y-m-d',
        ]);

        $manager = LoanManager::findOrFail($request->manager_id);
        $currentExpiry = $manager->subscription_expires_at ? Carbon::parse($manager->subscription_expires_at) : Carbon::now();
        
        // If expired, start from NOW. If active, add to existing time.
        if ($currentExpiry->isPast()) {
            $currentExpiry = Carbon::now();
        }

        switch ($request->duration) {
            case '1_month':
                $newExpiry = $currentExpiry->addMonth();
                break;
            case '3_months':
                $newExpiry = $currentExpiry->addMonths(3);
                break;
            case '6_months':
                $newExpiry = $currentExpiry->addMonths(6);
                break;
            case '1_year':
                $newExpiry = $currentExpiry->addYear();
                break;
            case 'custom':
                $newExpiry = Carbon::parse($request->custom_date)->endOfDay();
                break;
            case 'deactivate':
                $newExpiry = Carbon::now()->subDay(); // Set to yesterday to expire immediately
                break;
            default:
                $newExpiry = $currentExpiry;
        }

        $manager->subscription_expires_at = $newExpiry;
        $manager->save();

        return back()->with('success', 'Subscription updated! New expiry: ' . $newExpiry->format('d M Y'));
    }
}