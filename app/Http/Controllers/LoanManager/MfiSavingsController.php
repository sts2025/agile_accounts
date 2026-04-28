<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MfiSavingsController extends Controller
{
    // 1. List all Savings Accounts
    public function index()
    {
        $user = Auth::user();
        
        $accounts = DB::table('mfi_accounts')
            ->join('clients', 'mfi_accounts.client_id', '=', 'clients.id')
            ->where('mfi_accounts.loan_manager_id', $user->id)
            ->where('mfi_accounts.account_type', 'savings')
            ->select('mfi_accounts.*', 'clients.name as client_name', 'clients.phone_number')
            ->orderBy('mfi_accounts.created_at', 'desc')
            ->get();

        return view('loan-manager.mfi.savings.index', compact('accounts'));
    }

    // 2. Form to Open a New Account
    public function create()
    {
        $user = Auth::user();
        $clients = DB::table('clients')->where('loan_manager_id', $user->id)->get();
        
        // Auto-create a default 'Daily Savings' product if it doesn't exist
        $product = DB::table('mfi_products')
            ->where('loan_manager_id', $user->id)
            ->where('product_type', 'savings')
            ->first();

        if (!$product) {
            $productId = DB::table('mfi_products')->insertGetId([
                'loan_manager_id' => $user->id,
                'name' => 'Standard Daily Savings',
                'product_type' => 'savings',
                'interest_rate' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $product = DB::table('mfi_products')->where('id', $productId)->first();
        }

        return view('loan-manager.mfi.savings.create', compact('clients', 'product'));
    }

    // 3. Save the Account & Log the Initial Deposit
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'product_id' => 'required|integer',
            'opening_balance' => 'required|numeric|min:0'
        ]);

        $user = Auth::user();
        // Generate a smart account number (e.g., SAV-8F2A)
        $accountNumber = 'SAV-' . strtoupper(substr(uniqid(), -6)); 

        DB::transaction(function () use ($request, $user, $accountNumber) {
            
            // A. Create the Savings Account
            $accountId = DB::table('mfi_accounts')->insertGetId([
                'loan_manager_id' => $user->id,
                'client_id' => $request->client_id,
                'mfi_product_id' => $request->product_id,
                'account_number' => $accountNumber,
                'account_type' => 'savings',
                'balance' => $request->opening_balance,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // B. If they deposited money today, log it as a transaction
            if ($request->opening_balance > 0) {
                DB::table('mfi_transactions')->insert([
                    'loan_manager_id' => $user->id,
                    'client_id' => $request->client_id,
                    'mfi_account_id' => $accountId,
                    'transaction_type' => 'deposit',
                    'amount' => $request->opening_balance,
                    'debit' => 0,
                    'credit' => $request->opening_balance, // Money coming IN
                    'transaction_date' => now(),
                    'narration' => 'Opening Balance Deposit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('mfi.savings.index')->with('success', 'Savings Account opened successfully!');
    }
}