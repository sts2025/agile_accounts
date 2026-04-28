<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MfiAccount;
use App\Models\MfiTransaction;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    public function index()
    {
        // Get the current manager's ID
        $managerId = Auth::user()->id;
        
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
        $managerId = Auth::user()->loanManager->id ?? Auth::id();
        $clients = Client::where('loan_manager_id', $managerId)->orderBy('name')->get();
        
        return view('loan-manager.mfi.savings.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $managerId) {
                
                // FIX: Ensure a valid MFI Product exists instead of hardcoding ID 0
                $product = DB::table('mfi_products')
                    ->where('loan_manager_id', $managerId)
                    ->where('product_type', 'savings')
                    ->first();

                if (!$product) {
                    $productId = DB::table('mfi_products')->insertGetId([
                        'loan_manager_id' => $managerId,
                        'name' => 'Standard Daily Savings',
                        'product_type' => 'savings',
                        'interest_rate' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $productId = $product->id;
                }

                // 1. Create the Savings Account
                $account = MfiAccount::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $validated['client_id'],
                    'mfi_product_id' => $productId, // <-- Replaced '0' with the valid $productId
                    'account_number' => 'SAV-' . time() . rand(10,99),
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
        $managerId = Auth::user()->id;
        $account = MfiAccount::where('loan_manager_id', $managerId)
                    ->with(['client', 'transactions' => function($q) {
                        $q->latest('transaction_date');
                    }])
                    ->findOrFail($id);
                    
        return view('loan-manager.mfi.savings.show', compact('account'));
    }

    // --- NEW METHOD: Handle Deposits & Withdrawals ---
    public function transaction(Request $request, $id)
    {
        $managerId = Auth::user()->id;
        $account = MfiAccount::where('loan_manager_id', $managerId)->findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:deposit,withdrawal',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'narration' => 'nullable|string'
        ]);

        // Prevent withdrawing more than they have
        if ($validated['type'] == 'withdrawal' && $account->balance < $validated['amount']) {
            return back()->with('error', 'Insufficient funds! Available balance is ' . number_format($account->balance));
        }

        try {
            DB::transaction(function () use ($validated, $account, $managerId) {
                // 1. Log the transaction
                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $account->client_id,
                    'mfi_account_id' => $account->id,
                    'transaction_type' => $validated['type'],
                    'amount' => $validated['amount'],
                    'credit' => $validated['type'] == 'deposit' ? $validated['amount'] : 0,
                    'debit' => $validated['type'] == 'withdrawal' ? $validated['amount'] : 0,
                    'transaction_date' => now(),
                    'payment_method' => $validated['payment_method'],
                    'narration' => $validated['narration'] ?? ucfirst($validated['type'])
                ]);

                // 2. Update the main account balance
                if ($validated['type'] == 'deposit') {
                    $account->increment('balance', $validated['amount']);
                } else {
                    $account->decrement('balance', $validated['amount']);
                }
            });

            return back()->with('success', ucfirst($validated['type']) . ' processed successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Transaction failed: ' . $e->getMessage());
        }
    }
}