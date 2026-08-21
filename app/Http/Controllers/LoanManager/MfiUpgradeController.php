<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MfiUpgradeController extends Controller
{
    /**
     * Grant a tenant MFI Hub access. This is an admin-only action (see
     * routes/web.php — 'admin.managers.upgrade-mfi', behind the ['admin']
     * middleware): the MFI Hub is a paid add-on tier, not something a
     * loan manager can switch on themselves. $id is loan_managers.id, the
     * same tenant id used throughout the rest of the app, so it comes
     * straight from the admin panel's manager list rather than
     * Auth::user() the way the old self-service version worked.
     */
    public function upgradeToMfi(Request $request, $id)
    {
        // 1. Check if they already upgraded
        $managerProfile = DB::table('loan_managers')->where('id', $id)->first();

        if (!$managerProfile) {
            return redirect()->back()->with('error', 'No business profile found for that manager.');
        }

        if ($managerProfile->is_mfi) {
            return redirect()->back()->with('error', 'That account is already upgraded to MFI!');
        }

        // IMPORTANT: every table in this app (clients, loans, expenses, etc.)
        // stores loan_managers.id in its loan_manager_id column, NOT users.id.
        // The new mfi_products/mfi_accounts/mfi_transactions tables must use
        // the same tenant id so they line up with everything else.
        $managerId = $managerProfile->id;

        try {
            // Start the safe database transaction
            DB::transaction(function () use ($managerId) {

                // A. Create a default "Legacy" MFI Product for their old loans
                $productId = DB::table('mfi_products')->insertGetId([
                    'loan_manager_id' => $managerId,
                    'name' => 'Standard Legacy Loan',
                    'product_type' => 'loan',
                    'interest_rate' => 0,
                    'rules' => json_encode(['legacy_import' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // B. Fetch all their existing loans
                $loans = DB::table('loans')->where('loan_manager_id', $managerId)->get();
                $loanMap = []; // This remembers how old IDs match to new IDs

                foreach ($loans as $loan) {
                    $accountId = DB::table('mfi_accounts')->insertGetId([
                        'loan_manager_id' => $managerId,
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
                    ->where('loans.loan_manager_id', $managerId)
                    ->select('payments.*')
                    ->get();

                foreach ($payments as $payment) {
                    if (isset($loanMap[$payment->loan_id])) {
                        DB::table('mfi_transactions')->insert([
                            'loan_manager_id' => $managerId,
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
                    ->where('id', $managerId)
                    ->update(['is_mfi' => 1]);
            });

            return redirect()->route('admin.dashboard')->with('success', 'MFI Hub granted to ' . ($managerProfile->company_name ?? 'this manager') . '. Their existing loan data has been integrated.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Upgrade failed: ' . $e->getMessage());
        }
    }
}