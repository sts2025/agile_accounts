<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use Exception;

class SavingsTransactionController extends Controller
{
    /**
     * Handle a Deposit (Money In)
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'savings_account_id' => 'required|exists:savings_accounts,id',
            'amount' => 'required|numeric|min:1000', // Set a reasonable minimum, e.g., 1000 UGX
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            // DB::transaction ensures that if anything fails, the database rolls back completely.
            DB::transaction(function () use ($request) {
                // lockForUpdate() prevents other requests from modifying this account until we are done
                $account = SavingsAccount::where('id', $request->savings_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 1. Record the Transaction
                SavingsTransaction::create([
                    'savings_account_id' => $account->id,
                    'type' => 'deposit',
                    'amount' => $request->amount,
                    'reference' => $request->reference,
                    'performed_by' => auth()->id(),
                ]);

                // 2. Update the Balance
                $account->balance += $request->amount;
                $account->save();
            });

            return back()->with('success', 'Deposit recorded successfully.');

        } catch (Exception $e) {
            // Log the error for your IT team to investigate
            \Log::error('Deposit failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to process deposit. Please try again.');
        }
    }

    /**
     * Handle a Withdrawal (Money Out)
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'savings_account_id' => 'required|exists:savings_accounts,id',
            'amount' => 'required|numeric|min:1000',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $account = SavingsAccount::where('id', $request->savings_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // MFI LOGIC: Calculate Available Balance
                // They cannot withdraw money that is locked (lien) as security for a loan!
                $availableBalance = $account->balance - $account->lien_amount;

                if ($request->amount > $availableBalance) {
                    throw new Exception("Insufficient available funds. Available: UGX " . number_format($availableBalance));
                }

                // 1. Record the Transaction
                SavingsTransaction::create([
                    'savings_account_id' => $account->id,
                    'type' => 'withdrawal',
                    'amount' => $request->amount, // Usually recorded as positive in DB, the 'type' tells us it's a deduction
                    'reference' => $request->reference,
                    'performed_by' => auth()->id(),
                ]);

                // 2. Update the Balance
                $account->balance -= $request->amount;
                $account->save();
            });

            return back()->with('success', 'Withdrawal processed successfully.');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}