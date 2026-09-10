<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Services\LoanClassificationService;
use Illuminate\Support\Facades\Auth;

/**
 * Per-staff-member ("field officer") portfolio scorecard, built on top of
 * the clients.assigned_user_id field added for customer account settings
 * (see StatutoryReserveController-adjacent migration
 * 2026_08_23_000003_add_account_settings_to_clients_table) — that field
 * previously had no reporting use anywhere in the app. Reuses
 * LoanClassificationService::forManager() so the portfolio/PAR30 figures
 * here always agree with the Loan Classification and Prudential Returns
 * reports rather than re-deriving their own version of "days late".
 */
class OfficerPerformanceController extends Controller
{
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;
        $manager = Auth::user()->loanManager;

        // Every staff member who could plausibly be an "officer": the
        // tenant owner plus any cashier accounts under them.
        $officers = collect();
        if ($manager->user) {
            $officers->put($manager->user->id, $manager->user->name . ' (Owner)');
        }
        foreach ($manager->staff as $cashier) {
            $officers->put($cashier->id, $cashier->name . ' (Cashier)');
        }

        $clientCounts = Client::where('loan_manager_id', $managerId)
            ->selectRaw('assigned_user_id, count(*) as c')
            ->groupBy('assigned_user_id')
            ->pluck('c', 'assigned_user_id');

        $classification = LoanClassificationService::forManager($managerId);

        $portfolio = [];
        $par30 = [];
        $activeLoans = [];
        foreach ($classification['rows'] as $row) {
            $officerId = $row['loan']->client?->assigned_user_id;
            $key = $officerId ?? 0;
            $portfolio[$key] = ($portfolio[$key] ?? 0) + $row['outstanding'];
            $activeLoans[$key] = ($activeLoans[$key] ?? 0) + 1;
            if ($row['days_late'] >= 31) {
                $par30[$key] = ($par30[$key] ?? 0) + $row['outstanding'];
            }
        }

        $collections = Payment::whereHas('loan', function ($q) use ($managerId) {
                $q->where('loan_manager_id', $managerId);
            })
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->with('loan.client:id,assigned_user_id')
            ->get()
            ->groupBy(fn ($p) => $p->loan?->client?->assigned_user_id ?? 0)
            ->map(fn ($group) => $group->sum('amount_paid'));

        $rows = [];
        foreach ($officers as $userId => $label) {
            $rows[] = [
                'label' => $label,
                'clients' => $clientCounts[$userId] ?? 0,
                'active_loans' => $activeLoans[$userId] ?? 0,
                'portfolio_outstanding' => $portfolio[$userId] ?? 0,
                'par30_outstanding' => $par30[$userId] ?? 0,
                'collections_mtd' => $collections[$userId] ?? 0,
            ];
        }

        // Unassigned bucket — clients nobody has been assigned to yet.
        $unassignedClients = $clientCounts[null] ?? 0;
        if ($unassignedClients > 0 || ($portfolio[0] ?? 0) > 0) {
            $rows[] = [
                'label' => 'Unassigned',
                'clients' => $unassignedClients,
                'active_loans' => $activeLoans[0] ?? 0,
                'portfolio_outstanding' => $portfolio[0] ?? 0,
                'par30_outstanding' => $par30[0] ?? 0,
                'collections_mtd' => $collections[0] ?? 0,
            ];
        }

        return view('loan-manager.reports.officer-performance', [
            'rows' => $rows,
            'month' => now()->format('F Y'),
        ]);
    }
}
