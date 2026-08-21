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
use Carbon\Carbon;
use Exception;

class MfiFixedDepositController extends Controller
{
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        $accounts = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'fixed_deposit')
            ->with('client')
            ->latest()
            ->get();

        return view('loan-manager.mfi.fixed-deposits.index', compact('accounts'));
    }

    public function create()
    {
        $managerId = Auth::user()->loanManager->id;
        $clients = Client::where('loan_manager_id', $managerId)->orderBy('name')->get();

        $products = MfiProduct::where('loan_manager_id', $managerId)
            ->where('product_type', 'fixed_deposit')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('loan-manager.mfi.fixed-deposits.create', compact('clients', 'products'));
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'mfi_product_id' => 'required|exists:mfi_products,id',
            'principal_amount' => 'required|numeric|min:1000',
            'start_date' => 'required|date',
            'nickname' => 'nullable|string|max:100',
        ]);

        try {
            $account = DB::transaction(function () use ($validated, $managerId) {
                $product = MfiProduct::where('loan_manager_id', $managerId)
                    ->where('id', $validated['mfi_product_id'])
                    ->where('product_type', 'fixed_deposit')
                    ->firstOrFail();

                $maturityDate = Carbon::parse($validated['start_date'])->addMonths($product->term_months);

                $account = MfiAccount::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $validated['client_id'],
                    'mfi_product_id' => $product->id,
                    'account_number' => 'FD-' . time() . rand(10, 99),
                    'nickname' => $validated['nickname'] ?? null,
                    'account_type' => 'fixed_deposit',
                    'principal_amount' => $validated['principal_amount'],
                    'balance' => $validated['principal_amount'],
                    'maturity_date' => $maturityDate->toDateString(),
                    'status' => 'active',
                ]);

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $validated['client_id'],
                    'mfi_account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $validated['principal_amount'],
                    'credit' => $validated['principal_amount'],
                    'debit' => 0,
                    'transaction_date' => $validated['start_date'],
                    'payment_method' => 'Cash',
                    'narration' => 'Fixed deposit opened — matures ' . $maturityDate->toFormattedDateString(),
                ]);

                JournalPoster::post($managerId, 'Fixed deposit opened — ' . $account->account_number, 'fixed_deposit_open', [
                    ['code' => '1000', 'debit' => $validated['principal_amount'], 'description' => 'Cash received'],
                    ['code' => '2100', 'credit' => $validated['principal_amount'], 'description' => 'Fixed deposit principal'],
                ], $account->account_number);

                return $account;
            });

            return redirect()->route('mfi.fixed-deposits.show', $account->id)->with('success', 'Fixed deposit opened successfully!');

        } catch (Exception $e) {
            return back()->with('error', 'Error opening fixed deposit: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $managerId = Auth::user()->loanManager->id;

        $account = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'fixed_deposit')
            ->with(['client', 'transactions' => function ($q) {
                $q->latest('transaction_date');
            }])
            ->findOrFail($id);

        $product = MfiProduct::find($account->mfi_product_id);

        $isMatured = $account->maturity_date && Carbon::parse($account->maturity_date)->isPast();
        $projectedInterest = $product ? round($account->principal_amount * ($product->interest_rate / 100), 2) : 0;
        $projectedPayout = $account->principal_amount + $projectedInterest;

        return view('loan-manager.mfi.fixed-deposits.show', compact('account', 'product', 'isMatured', 'projectedInterest', 'projectedPayout'));
    }

    /**
     * Close the deposit and pay principal + (penalty-adjusted) interest out
     * into the client's savings account. Only the interest is ever at risk
     * from an early-withdrawal penalty — principal is always paid in full.
     */
    public function close(Request $request, $id)
    {
        $managerId = Auth::user()->loanManager->id;

        try {
            DB::transaction(function () use ($id, $managerId) {
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'fixed_deposit')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status !== 'active') {
                    throw new Exception('This fixed deposit has already been closed.');
                }

                $product = MfiProduct::find($account->mfi_product_id);
                $isMatured = $account->maturity_date && Carbon::parse($account->maturity_date)->isPast();

                $interest = $product ? round($account->principal_amount * ($product->interest_rate / 100), 2) : 0;
                if (!$isMatured && $product) {
                    $forfeited = round($interest * ($product->early_withdrawal_penalty_percent / 100), 2);
                    $interest = max(0, $interest - $forfeited);
                }

                $payout = $account->principal_amount + $interest;

                $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('client_id', $account->client_id)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (!$savingsAccount) {
                    throw new Exception('This client has no active savings account to pay the deposit out into. Open one first, then close this deposit.');
                }

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $account->client_id,
                    'mfi_account_id' => $account->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $payout,
                    'credit' => 0,
                    'debit' => $payout,
                    'transaction_date' => now(),
                    'payment_method' => 'Internal',
                    'narration' => 'Closed and paid out to savings account ' . $savingsAccount->account_number
                        . ($isMatured ? ' (matured)' : ' (early withdrawal — penalty applied to interest)'),
                ]);

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $account->client_id,
                    'mfi_account_id' => $savingsAccount->id,
                    'transaction_type' => 'deposit',
                    'amount' => $payout,
                    'credit' => $payout,
                    'debit' => 0,
                    'transaction_date' => now(),
                    'payment_method' => 'Internal',
                    'narration' => 'Fixed deposit ' . $account->account_number . ' matured/closed',
                ]);

                $savingsAccount->increment('balance', $payout);
                $account->update(['balance' => 0, 'status' => 'closed']);

                // Principal comes out of the Fixed Deposits liability; any
                // interest paid is a real expense to the institution. Both
                // land in Member Savings Deposits since the payout is an
                // internal transfer into the client's wallet, not cash out.
                $journalLines = [
                    ['code' => '2100', 'debit' => $account->principal_amount, 'description' => 'Fixed deposit principal released'],
                ];
                if ($interest > 0) {
                    $journalLines[] = ['code' => '5100', 'debit' => $interest, 'description' => 'Interest on fixed deposit'];
                }
                $journalLines[] = ['code' => '2000', 'credit' => $payout, 'description' => 'Paid into savings on FD close'];

                JournalPoster::post($managerId, 'Fixed deposit closed — ' . $account->account_number, 'fixed_deposit_close', $journalLines, $account->account_number);
            });

            return redirect()->route('mfi.fixed-deposits.index')->with('success', 'Fixed deposit closed and paid out to savings.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
