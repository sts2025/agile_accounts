<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\MfiAccount;
use App\Models\MfiTransaction;
use App\Models\MfiProduct;
use App\Models\Client;
use App\Services\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class MfiShareController extends Controller
{
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        $accounts = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'shares')
            ->with('client')
            ->latest()
            ->get();

        $totalUnits = $accounts->where('status', 'active')->sum('units');

        return view('loan-manager.mfi.shares.index', compact('accounts', 'totalUnits'));
    }

    public function create()
    {
        $managerId = Auth::user()->loanManager->id;
        $clients = Client::where('loan_manager_id', $managerId)->orderBy('name')->get();

        $products = MfiProduct::where('loan_manager_id', $managerId)
            ->where('product_type', 'shares')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('loan-manager.mfi.shares.create', compact('clients', 'products'));
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'mfi_product_id' => 'required|exists:mfi_products,id',
            'units' => 'required|numeric|min:0',
            'nickname' => 'nullable|string|max:100',
        ]);

        try {
            $account = DB::transaction(function () use ($validated, $managerId) {
                $product = MfiProduct::where('loan_manager_id', $managerId)
                    ->where('id', $validated['mfi_product_id'])
                    ->where('product_type', 'shares')
                    ->firstOrFail();

                $account = MfiAccount::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $validated['client_id'],
                    'mfi_product_id' => $product->id,
                    'account_number' => 'SHR-' . time() . rand(10, 99),
                    'nickname' => $validated['nickname'] ?? null,
                    'account_type' => 'shares',
                    'balance' => 0,
                    'units' => 0,
                    'status' => 'active',
                ]);

                if ($validated['units'] > 0) {
                    $this->purchaseShares($account, $product, (float) $validated['units'], $managerId);
                }

                return $account;
            });

            return redirect()->route('mfi.shares.show', $account->id)->with('success', 'Share account opened successfully!');

        } catch (Exception $e) {
            return back()->with('error', 'Error opening share account: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $managerId = Auth::user()->loanManager->id;

        $account = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'shares')
            ->with(['client', 'transactions' => function ($q) {
                $q->latest('transaction_date');
            }])
            ->findOrFail($id);

        $product = MfiProduct::find($account->mfi_product_id);

        return view('loan-manager.mfi.shares.show', compact('account', 'product'));
    }

    /**
     * Buy additional shares for an existing account.
     */
    public function buy(Request $request, $id)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'units' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($validated, $id, $managerId) {
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'shares')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status === 'closed') {
                    throw new Exception('This share account is closed.');
                }

                $product = MfiProduct::findOrFail($account->mfi_product_id);

                $this->purchaseShares($account, $product, (float) $validated['units'], $managerId);
            });

            return back()->with('success', 'Shares purchased successfully.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Redeem (cash out) shares — typically used when a member reduces their
     * holding or exits the SACCO. Pays out at the product's current share
     * value.
     */
    public function redeem(Request $request, $id)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'units' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($validated, $id, $managerId) {
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'shares')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status === 'closed') {
                    throw new Exception('This share account is closed.');
                }

                if ($validated['units'] > $account->units) {
                    throw new Exception('Cannot redeem more shares than the member holds (' . rtrim(rtrim(number_format($account->units, 4), '0'), '.') . ' held).');
                }

                $product = MfiProduct::findOrFail($account->mfi_product_id);
                $payout = round($validated['units'] * $product->share_value, 2);

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $account->client_id,
                    'mfi_account_id' => $account->id,
                    'transaction_type' => 'share_redemption',
                    'amount' => $payout,
                    'credit' => 0,
                    'debit' => $payout,
                    'transaction_date' => now(),
                    'payment_method' => 'Cash',
                    'narration' => 'Redeemed ' . $validated['units'] . ' shares',
                ]);

                $account->decrement('units', $validated['units']);
                $account->decrement('balance', $payout);

                JournalPoster::post($managerId, 'Share redemption — ' . $account->account_number, 'share_redemption', [
                    ['code' => '3000', 'debit' => $payout, 'description' => 'Shares redeemed'],
                    ['code' => '1000', 'credit' => $payout, 'description' => 'Cash paid out'],
                ], $account->account_number);
            });

            return back()->with('success', 'Shares redeemed successfully.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Close a share account — redeems every remaining unit at the product's
     * current share value (same payout logic as redeem()) and marks the
     * account closed. A member with zero units can also close outright.
     */
    public function closeAccount($id)
    {
        $managerId = Auth::user()->loanManager->id;

        try {
            DB::transaction(function () use ($id, $managerId) {
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'shares')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status === 'closed') {
                    throw new Exception('This share account is already closed.');
                }

                if ($account->units > 0) {
                    $product = MfiProduct::find($account->mfi_product_id);
                    $payout = round($account->units * ($product->share_value ?? 0), 2);

                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $account->client_id,
                        'mfi_account_id' => $account->id,
                        'transaction_type' => 'share_redemption',
                        'amount' => $payout,
                        'credit' => 0,
                        'debit' => $payout,
                        'transaction_date' => now(),
                        'payment_method' => 'Cash',
                        'narration' => 'Redeemed all ' . $account->units . ' shares — account closed',
                    ]);

                    JournalPoster::post($managerId, 'Share account closed — ' . $account->account_number, 'share_close', [
                        ['code' => '3000', 'debit' => $payout, 'description' => 'Shares redeemed — account closed'],
                        ['code' => '1000', 'credit' => $payout, 'description' => 'Cash paid out'],
                    ], $account->account_number);

                    $account->units = 0;
                    $account->balance = 0;
                }

                $account->status = 'closed';
                $account->save();
            });

            return redirect()->route('mfi.shares.index')->with('success', 'Share account closed.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function purchaseShares(MfiAccount $account, MfiProduct $product, float $units, int $managerId): void
    {
        $cost = round($units * $product->share_value, 2);

        MfiTransaction::create([
            'loan_manager_id' => $managerId,
            'client_id' => $account->client_id,
            'mfi_account_id' => $account->id,
            'transaction_type' => 'share_purchase',
            'amount' => $cost,
            'credit' => $cost,
            'debit' => 0,
            'transaction_date' => now(),
            'payment_method' => 'Cash',
            'narration' => 'Purchased ' . $units . ' shares',
        ]);

        $account->increment('units', $units);
        $account->increment('balance', $cost);

        JournalPoster::post($managerId, 'Share purchase — ' . $account->account_number, 'share_purchase', [
            ['code' => '1000', 'debit' => $cost, 'description' => 'Cash received'],
            ['code' => '3000', 'credit' => $cost, 'description' => 'Shares issued'],
        ], $account->account_number);
    }
}
