<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\MfiAccount;
use App\Models\MfiTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class MfiDividendController extends Controller
{
    public function create()
    {
        $managerId = Auth::user()->loanManager->id;

        $totalUnits = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'shares')
            ->where('status', 'active')
            ->sum('units');

        return view('loan-manager.mfi.dividends.create', compact('totalUnits'));
    }

    /**
     * Show a proportional payout preview without moving any money, so the
     * manager can review before committing.
     */
    public function preview(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'pool_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $accounts = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'shares')
            ->where('status', 'active')
            ->where('units', '>', 0)
            ->with('client')
            ->get();

        $totalUnits = $accounts->sum('units');

        if ($totalUnits <= 0) {
            return back()->with('error', 'No shareholders with units to distribute a dividend to.')->withInput();
        }

        $clientSavings = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->get()
            ->keyBy('client_id');

        $rows = $accounts->map(function ($account) use ($validated, $totalUnits, $clientSavings) {
            $payout = round(($account->units / $totalUnits) * $validated['pool_amount'], 2);
            return (object) [
                'client_name' => $account->client->name ?? 'Unknown',
                'units' => $account->units,
                'payout' => $payout,
                'has_savings' => $clientSavings->has($account->client_id),
            ];
        })->sortByDesc('payout');

        return view('loan-manager.mfi.dividends.preview', [
            'rows' => $rows,
            'totalUnits' => $totalUnits,
            'poolAmount' => $validated['pool_amount'],
            'description' => $validated['description'] ?? null,
        ]);
    }

    /**
     * Execute the distribution: credit each shareholder's savings account
     * with their proportional payout. Amounts are recomputed here from the
     * database, not trusted from the preview form, so nothing can be
     * tampered with between preview and submit.
     */
    public function distribute(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'pool_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $skipped = [];
        $paidCount = 0;
        $paidTotal = 0;

        try {
            DB::transaction(function () use ($validated, $managerId, &$skipped, &$paidCount, &$paidTotal) {
                $accounts = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'shares')
                    ->where('status', 'active')
                    ->where('units', '>', 0)
                    ->lockForUpdate()
                    ->with('client')
                    ->get();

                $totalUnits = $accounts->sum('units');

                if ($totalUnits <= 0) {
                    throw new Exception('No shareholders with units to distribute a dividend to.');
                }

                foreach ($accounts as $shareAccount) {
                    $payout = round(($shareAccount->units / $totalUnits) * $validated['pool_amount'], 2);
                    if ($payout <= 0) {
                        continue;
                    }

                    $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
                        ->where('client_id', $shareAccount->client_id)
                        ->where('account_type', 'savings')
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->first();

                    if (!$savingsAccount) {
                        $skipped[] = ($shareAccount->client->name ?? ('Client #' . $shareAccount->client_id)) . ' (' . number_format($payout) . ')';
                        continue;
                    }

                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $shareAccount->client_id,
                        'mfi_account_id' => $savingsAccount->id,
                        'transaction_type' => 'dividend',
                        'amount' => $payout,
                        'credit' => $payout,
                        'debit' => 0,
                        'transaction_date' => now(),
                        'payment_method' => 'Internal',
                        'narration' => 'Dividend' . (!empty($validated['description']) ? ': ' . $validated['description'] : ''),
                    ]);

                    $savingsAccount->increment('balance', $payout);
                    $paidCount++;
                    $paidTotal += $payout;
                }
            });

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $message = "Dividend distributed to {$paidCount} member(s), totaling " . number_format($paidTotal) . ".";
        if (!empty($skipped)) {
            $message .= ' Skipped — no active savings account to receive payout: ' . implode(', ', $skipped) . '. Pay these manually.';
        }

        return redirect()->route('mfi.shares.index')->with('success', $message);
    }
}
