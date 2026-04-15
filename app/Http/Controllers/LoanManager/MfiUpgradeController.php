<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MfiUpgradeController extends Controller
{
    public function upgradeToMfi(Request $request)
    {
        $user = Auth::user();

        // 1. Check if they already upgraded
        $managerProfile = DB::table('loan_managers')->where('user_id', $user->id)->first() 
                          ?? DB::table('loan_managers')->where('id', $user->id)->first();

        if ($managerProfile && $managerProfile->is_mfi) {
            return redirect()->back()->with('error', 'Your account is already upgraded to MFI!');
        }

        try {
            // Start the safe database transaction
            DB::transaction(function () use ($user) {
                
                // A. Create a default "Legacy" MFI Product for their old loans
                $productId = DB::table('mfi_products')->insertGetId([
                    'loan_manager_id' => $user->id,
                    'name' => 'Standard Legacy Loan',
                    'product_type' => 'loan',
                    'interest_rate' => 0, 
                    'rules' => json_encode(['legacy_import' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // B. Fetch all their existing loans
                $loans = DB::table('loans')->where('loan_manager_id', $user->id)->get();
                $loanMap = []; // This remembers how old IDs match to new IDs

                foreach ($loans as $loan) {
                    $accountId = DB::table('mfi_accounts')->insertGetId([
                        'loan_manager_id' => $user->id,
                        'client_id' => $loan->client_id,
                        'mfi_product_id' => $productId,
                        'account_number' => 'ACC-' . str_pad($loan->id, 6, '0', STR_PAD_LEFT),
                        'account_type' => 'loan',
                        'principal_amount' => $loan->principal_amount,
                        'term' => $loan->term,
                        'balance' => $loan->principal_amount, 
                        'status' => $loan->status,
                        'created_at' => $loan->created_at,
                        'updated_at' => $loan->updated_at,
                    ]);
                    // Save the map for the payments step
                    $loanMap[$loan->id] = [
                        'account_id' => $accountId,
                        'client_id' => $loan->client_id
                    ];
                }

                // C. Fetch and migrate all payments into Transactions
                $payments = DB::table('payments')
                    ->join('loans', 'payments.loan_id', '=', 'loans.id')
                    ->where('loans.loan_manager_id', $user->id)
                    ->select('payments.*')
                    ->get();

                foreach ($payments as $payment) {
                    if (isset($loanMap[$payment->loan_id])) {
                        DB::table('mfi_transactions')->insert([
                            'loan_manager_id' => $user->id,
                            'client_id' => $loanMap[$payment->loan_id]['client_id'],
                            'mfi_account_id' => $loanMap[$payment->loan_id]['account_id'],
                            'transaction_type' => 'loan_repayment',
                            'amount' => $payment->amount_paid,
                            'debit' => 0,
                            'credit' => $payment->amount_paid, // Money coming IN
                            'transaction_date' => $payment->payment_date,
                            'payment_method' => $payment->payment_method ?? 'Cash',
                            'reference_number' => $payment->receipt_number,
                            'narration' => 'Legacy Repayment Import',
                            'created_at' => $payment->created_at,
                            'updated_at' => $payment->updated_at,
                        ]);
                    }
                }

                // D. Finally, flip the switch!
                DB::table('loan_managers')
                    ->where('id', $managerProfile->id ?? $user->id)
                    ->update(['is_mfi' => 1]);
            });

            return redirect()->route('dashboard')->with('success', 'Welcome to Microfinance! Your data has been successfully integrated.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Upgrade failed: ' . $e->getMessage());
        }
    }
}