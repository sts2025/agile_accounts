<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\MfiAccount;
use App\Models\MfiTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * End-of-Period processing: currently covers savings interest application
 * (the standard SACCO/MFI "EOD/EOM interest posting" job). Fixed deposit
 * interest is intentionally left out of this run — it's already computed
 * and realised in one shot at closure (MfiFixedDepositController@close),
 * consistent with how the rest of this app recognises income on a cash
 * basis rather than accruing it ahead of time.
 */
class MfiEndOfPeriodController extends Controller
{
    public function index()
    {
        return view('loan-manager.mfi.end-of-period.index');
    }

    /**
     * Show what interest would be posted, without writing anything.
     */
    public function preview(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'as_of_date' => 'required|date|before_or_equal:today',
        ]);

        $asOfDate = Carbon::parse($validated['as_of_date'])->endOfDay();

        $accounts = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->with('client', 'mfiProduct')
            ->get();

        $rows = collect();

        foreach ($accounts as $account) {
            $result = $this->computeInterestForAccount($account, $asOfDate);

            if (!$result) {
                continue;
            }

            $rows->push((object) [
                'client_name' => $account->client->name ?? 'Unknown',
                'account_number' => $account->account_number,
                'balance' => $account->balance,
                'rate' => $result['rate'],
                'days' => $result['days'],
                'interest' => $result['interest'],
            ]);
        }

        $totalInterest = $rows->sum('interest');

        return view('loan-manager.mfi.end-of-period.preview', [
            'rows' => $rows,
            'totalInterest' => $totalInterest,
            'asOfDate' => $validated['as_of_date'],
        ]);
    }

    /**
     * Execute the posting. Everything is recomputed here from the database
     * (never trusted from the preview form) inside a locked transaction, so
     * nothing can be tampered with between preview and submit, and a
     * double-submit can't double-post the same period.
     */
    public function post(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'as_of_date' => 'required|date|before_or_equal:today',
        ]);

        $asOfDate = Carbon::parse($validated['as_of_date'])->endOfDay();
        $postedCount = 0;
        $postedTotal = 0;

        try {
            DB::transaction(function () use ($managerId, $asOfDate, &$postedCount, &$postedTotal) {
                $accounts = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->where('balance', '>', 0)
                    ->with('mfiProduct')
                    ->lockForUpdate()
                    ->get();

                foreach ($accounts as $account) {
                    $result = $this->computeInterestForAccount($account, $asOfDate);

                    if (!$result) {
                        continue;
                    }

                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $account->client_id,
                        'mfi_account_id' => $account->id,
                        'transaction_type' => 'interest',
                        'amount' => $result['interest'],
                        'credit' => $result['interest'],
                        'debit' => 0,
                        'transaction_date' => $asOfDate->toDateString(),
                        'payment_method' => 'Internal',
                        'narration' => 'Interest posted for ' . $result['days'] . ' day(s) at ' . number_format($result['rate'], 2) . '% p.a.',
                    ]);

                    $account->increment('balance', $result['interest']);
                    $account->last_interest_posted_at = $asOfDate->toDateString();
                    $account->save();

                    $postedCount++;
                    $postedTotal += $result['interest'];
                }
            });
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($postedCount === 0) {
            return redirect()->route('mfi.end-of-period.index')
                ->with('success', 'No interest was due — every account is already posted up to this date, or has no interest-bearing balance.');
        }

        return redirect()->route('mfi.savings.index')
            ->with('success', "Interest posted to {$postedCount} savings account(s), totaling " . number_format($postedTotal) . '.');
    }

    /**
     * Simple interest for one account: balance * annual_rate/100 * days/365,
     * accrued since the account's last posting date (or its opening date if
     * interest has never been posted). Returns null if the product has no
     * interest rate configured, or if there's no whole day of interest due
     * yet as of the requested date.
     */
    private function computeInterestForAccount(MfiAccount $account, Carbon $asOfDate): ?array
    {
        $product = $account->mfiProduct;
        $rate = $product ? (float) $product->interest_rate : 0;

        if ($rate <= 0) {
            return null;
        }

        $periodStart = $account->last_interest_posted_at
            ? Carbon::parse($account->last_interest_posted_at)->startOfDay()
            : Carbon::parse($account->created_at)->startOfDay();

        if (!$asOfDate->greaterThan($periodStart)) {
            return null;
        }

        $days = (int) $periodStart->diffInDays($asOfDate);

        if ($days <= 0) {
            return null;
        }

        $interest = round($account->balance * ($rate / 100) * ($days / 365), 2);

        if ($interest <= 0) {
            return null;
        }

        return ['interest' => $interest, 'days' => $days, 'rate' => $rate];
    }
}
