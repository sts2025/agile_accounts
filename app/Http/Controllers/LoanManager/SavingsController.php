<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MfiAccount;
use App\Models\MfiTransaction;
use App\Models\MfiProduct;
use App\Models\Client;
use App\Services\JournalPoster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    public function index()
    {
        // Get the current manager's ID
        // mfi_accounts/mfi_products/mfi_transactions.loan_manager_id stores
        // loan_managers.id, matching the convention used by clients/loans
        // everywhere else in the app.
        $managerId = Auth::user()->loanManager->id;
        
        // Fetch only 'savings' accounts (hiding the loan accounts we migrated earlier)
        $accounts = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->with('client')
                    ->latest()
                    ->get();
                    
        return view('loan-manager.mfi.savings.index', compact('accounts'));
    }

    public function create()
    {
        // Fetch clients so the manager can select who is opening the account
        $managerId = Auth::user()->loanManager->id;
        $clients = Client::where('loan_manager_id', $managerId)->orderBy('name')->get();

        // Let the manager pick from their configured savings products
        // (Product Settings). If they haven't configured any yet, the form
        // falls back to a single default account type.
        $products = MfiProduct::where('loan_manager_id', $managerId)
            ->where('product_type', 'savings')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('loan-manager.mfi.savings.create', compact('clients', 'products'));
    }

    public function store(Request $request)
    {
        // mfi_accounts/mfi_products/mfi_transactions.loan_manager_id stores
        // loan_managers.id, matching the convention used by clients/loans
        // everywhere else in the app.
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'mfi_product_id' => 'nullable|exists:mfi_products,id',
            'opening_balance' => 'required|numeric|min:0',
            'nickname' => 'nullable|string|max:100',
        ]);

        try {
            DB::transaction(function () use ($validated, $managerId) {

                if (!empty($validated['mfi_product_id'])) {
                    // Manager picked one of their configured savings products.
                    $productId = MfiProduct::where('loan_manager_id', $managerId)
                        ->where('id', $validated['mfi_product_id'])
                        ->where('product_type', 'savings')
                        ->firstOrFail()
                        ->id;
                } else {
                    // No products configured (or none picked) — fall back to a
                    // single default so account-opening still works out of the box.
                    $product = MfiProduct::where('loan_manager_id', $managerId)
                        ->where('product_type', 'savings')
                        ->first();

                    $productId = $product?->id ?? MfiProduct::create([
                        'loan_manager_id' => $managerId,
                        'name' => 'Standard Daily Savings',
                        'product_type' => 'savings',
                        'interest_rate' => 0,
                        'rules' => ['minimum_balance' => 0, 'is_compulsory' => false, 'allow_withdrawals' => true],
                        'is_active' => true,
                    ])->id;
                }

                // 1. Create the Savings Account
                $account = MfiAccount::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $validated['client_id'],
                    'mfi_product_id' => $productId, // <-- Replaced '0' with the valid $productId
                    'account_number' => 'SAV-' . time() . rand(10,99),
                    'nickname' => $validated['nickname'] ?? null,
                    'account_type' => 'savings',
                    'balance' => $validated['opening_balance'],
                    'status' => 'active',
                ]);

                // 2. If they deposited money immediately, record the transaction
                if ($validated['opening_balance'] > 0) {
                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $validated['client_id'],
                        'mfi_account_id' => $account->id,
                        'transaction_type' => 'deposit',
                        'amount' => $validated['opening_balance'],
                        'credit' => $validated['opening_balance'], // Money IN
                        'debit' => 0,
                        'transaction_date' => now(),
                        'payment_method' => 'Cash',
                        'narration' => 'Initial Deposit / Opening Balance'
                    ]);

                    JournalPoster::post($managerId, 'Savings account opened — ' . $account->account_number, 'savings_open', [
                        ['code' => '1000', 'debit' => $validated['opening_balance'], 'description' => 'Cash received'],
                        ['code' => '2000', 'credit' => $validated['opening_balance'], 'description' => 'Opening balance'],
                    ], $account->account_number);
                }
            });

            return redirect()->route('mfi.savings.index')->with('success', 'Savings Account successfully opened!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error opening account: ' . $e->getMessage());
        }
    }

    // --- NEW METHOD: Show Account Details ---
    public function show($id)
    {
        // mfi_accounts/mfi_products/mfi_transactions.loan_manager_id stores
        // loan_managers.id, matching the convention used by clients/loans
        // everywhere else in the app.
        $managerId = Auth::user()->loanManager->id;
        $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->with(['client', 'transactions' => function($q) {
                        $q->latest('transaction_date');
                    }])
                    ->findOrFail($id);

        return view('loan-manager.mfi.savings.show', compact('account'));
    }

    /**
     * Printable passbook: full transaction history in chronological order
     * (oldest first, like a real bank passbook) with a running balance.
     */
    public function passbook($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->with(['client', 'transactions' => function($q) {
                        $q->oldest('transaction_date')->oldest('id');
                    }])
                    ->findOrFail($id);

        return view('loan-manager.mfi.savings.passbook', compact('account'));
    }

    // --- Deposit: used by the "Deposit Cash" modal on the account show page ---
    public function deposit(Request $request)
    {
        // mfi_accounts/mfi_products/mfi_transactions.loan_manager_id stores
        // loan_managers.id, matching the convention used by clients/loans
        // everywhere else in the app.
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'savings_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:1000',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated, $managerId) {
                // lockForUpdate prevents a double-click / concurrent request from
                // crediting the same account twice.
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->where('id', $validated['savings_account_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status !== 'active') {
                    throw new \Exception('This account is ' . str_replace('_', ' ', $account->status) . ' and cannot accept transactions.');
                }

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $account->client_id,
                    'mfi_account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $validated['amount'],
                    'credit' => $validated['amount'],
                    'debit' => 0,
                    'transaction_date' => now(),
                    'payment_method' => 'Cash',
                    'reference_number' => $validated['reference'] ?? null,
                    'narration' => 'Cash deposit to savings',
                ]);

                $account->increment('balance', $validated['amount']);

                JournalPoster::post($managerId, 'Savings deposit — ' . $account->account_number, 'savings_deposit', [
                    ['code' => '1000', 'debit' => $validated['amount'], 'description' => 'Cash received'],
                    ['code' => '2000', 'credit' => $validated['amount'], 'description' => 'Savings deposit'],
                ], $account->account_number);
            });

            return back()->with('success', 'Deposit recorded successfully.');

        } catch (\Exception $e) {
            \Log::error('MFI deposit failed: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // --- Withdraw: used by the "Withdraw Cash" modal on the account show page ---
    public function withdraw(Request $request)
    {
        // mfi_accounts/mfi_products/mfi_transactions.loan_manager_id stores
        // loan_managers.id, matching the convention used by clients/loans
        // everywhere else in the app.
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'savings_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:1000',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated, $managerId) {
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->where('id', $validated['savings_account_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status !== 'active') {
                    throw new \Exception('This account is ' . str_replace('_', ' ', $account->status) . ' and cannot accept transactions.');
                }

                // Savings-led lending rule: funds held as loan collateral
                // (lien_amount) are not available for withdrawal.
                $availableBalance = $account->balance - $account->lien_amount;

                if ($validated['amount'] > $availableBalance) {
                    throw new \Exception(
                        'Insufficient available funds. Available to withdraw: ' .
                        number_format($availableBalance) .
                        ($account->lien_amount > 0 ? ' (' . number_format($account->lien_amount) . ' locked as loan collateral).' : '.')
                    );
                }

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $account->client_id,
                    'mfi_account_id' => $account->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $validated['amount'],
                    'credit' => 0,
                    'debit' => $validated['amount'],
                    'transaction_date' => now(),
                    'payment_method' => 'Cash',
                    'reference_number' => $validated['reference'] ?? null,
                    'narration' => 'Cash withdrawal from savings',
                ]);

                $account->decrement('balance', $validated['amount']);

                JournalPoster::post($managerId, 'Savings withdrawal — ' . $account->account_number, 'savings_withdrawal', [
                    ['code' => '2000', 'debit' => $validated['amount'], 'description' => 'Savings withdrawal'],
                    ['code' => '1000', 'credit' => $validated['amount'], 'description' => 'Cash paid out'],
                ], $account->account_number);
            });

            return back()->with('success', 'Withdrawal processed successfully.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Put a savings account on hold. Deposit/withdraw/passbook-relevant
     * queries elsewhere in this controller all filter on
     * ->where('status', 'active'), so simply moving it off 'active' is
     * enough to block transactions against it without duplicating that
     * check everywhere — the account still exists and shows its history,
     * it just can't move money while held.
     */
    public function putOnHold($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $account = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'savings')
            ->where('id', $id)
            ->firstOrFail();

        if ($account->status !== 'active') {
            return back()->with('error', 'Only an active account can be put on hold.');
        }

        $account->update(['status' => 'on_hold']);

        return back()->with('success', 'Account put on hold.');
    }

    public function takeOffHold($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $account = MfiAccount::where('loan_manager_id', $managerId)
            ->where('account_type', 'savings')
            ->where('id', $id)
            ->firstOrFail();

        if ($account->status !== 'on_hold') {
            return back()->with('error', 'This account isn\'t on hold.');
        }

        $account->update(['status' => 'active']);

        return back()->with('success', 'Account taken off hold.');
    }

    /**
     * Close a savings account. Any remaining balance is paid out as a
     * final cash withdrawal so the account closes at zero rather than
     * leaving money stranded in a closed record. Blocked while any of
     * the balance is locked as loan collateral — that has to be released
     * (by paying off or writing off the loan it secures) first.
     */
    public function closeAccount($id)
    {
        $managerId = Auth::user()->loanManager->id;

        try {
            DB::transaction(function () use ($id, $managerId) {
                $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('account_type', 'savings')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($account->status === 'closed') {
                    throw new \Exception('This account is already closed.');
                }

                if ($account->lien_amount > 0) {
                    throw new \Exception('Cannot close: ' . number_format($account->lien_amount) . ' of this balance is locked as loan collateral.');
                }

                if ($account->balance > 0) {
                    MfiTransaction::create([
                        'loan_manager_id' => $managerId,
                        'client_id' => $account->client_id,
                        'mfi_account_id' => $account->id,
                        'transaction_type' => 'withdrawal',
                        'amount' => $account->balance,
                        'credit' => 0,
                        'debit' => $account->balance,
                        'transaction_date' => now(),
                        'payment_method' => 'Cash',
                        'narration' => 'Final withdrawal — account closed',
                    ]);

                    JournalPoster::post($managerId, 'Savings account closed — ' . $account->account_number, 'savings_close', [
                        ['code' => '2000', 'debit' => $account->balance, 'description' => 'Closing balance paid out'],
                        ['code' => '1000', 'credit' => $account->balance, 'description' => 'Cash paid out'],
                    ], $account->account_number);

                    $account->balance = 0;
                }

                $account->status = 'closed';
                $account->save();
            });

            return back()->with('success', 'Account closed.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}