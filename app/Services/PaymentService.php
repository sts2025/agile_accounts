<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\MfiAccount;
use App\Models\MfiProduct;
use App\Models\MfiTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * The single place a loan repayment actually gets recorded — extracted
 * verbatim out of PaymentController::store() so the exact same accounting
 * (savings-from-loan, compulsory savings auto-split, loan payoff +
 * collateral release, General Journal posting, notifications) can be
 * reused by anything that needs to record a payment outside the single-
 * payment HTTP form, e.g. bulk group collection entry. PaymentController
 * itself now just validates the request and calls record() — behavior is
 * unchanged, only where the code lives.
 *
 * Throws \Exception (not a typed exception — matches the original
 * try/catch(\Exception) in PaymentController) for business-rule failures
 * a caller should show back to the user: loan not found, insufficient
 * savings balance, zero/negative amount.
 */
class PaymentService
{
    /**
     * @param array{loan_id:int, principal_paid:float, interest_paid:float, payment_date:string, payment_method:string, reference_id?:?string, notes?:?string, pay_from_savings?:bool} $data
     * @return array{payment: Payment, compulsory_split: float, loan: Loan, just_paid_off: bool}
     */
    public static function record(int $managerId, bool $isMfi, array $data): array
    {
        $totalAmount = $data['principal_paid'] + $data['interest_paid'];

        if ($totalAmount <= 0) {
            throw new \Exception('Payment amount must be greater than zero.');
        }

        // Only MFI tenants can pay from savings; ignore the flag otherwise.
        $payFromSavings = $isMfi && !empty($data['pay_from_savings']);

        $result = DB::transaction(function () use ($data, $totalAmount, $managerId, $isMfi, $payFromSavings) {

            $receiptNumber = !empty($data['reference_id'])
                                ? $data['reference_id']
                                : 'RCP-' . time() . rand(10, 99);

            $loan = Loan::with('client')->lockForUpdate()->find($data['loan_id']);

            if (!$loan || $loan->loan_manager_id !== $managerId) {
                throw new \Exception('Loan not found.');
            }

            $savingsAccount = null;

            // --- Repayment from savings: verify funds before touching anything ---
            if ($payFromSavings) {
                $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('client_id', $loan->client_id)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (!$savingsAccount) {
                    throw new \Exception('This client has no active savings account to pay from.');
                }

                $available = $savingsAccount->balance - $savingsAccount->lien_amount;

                if ($totalAmount > $available) {
                    throw new \Exception(
                        'Insufficient savings balance. Available to withdraw: ' .
                        number_format($available) .
                        ($savingsAccount->lien_amount > 0 ? ' (' . number_format($savingsAccount->lien_amount) . ' locked as loan collateral).' : '.')
                    );
                }
            }

            $newPayment = Payment::create([
                'loan_id'        => $data['loan_id'],
                'payment_date'   => $data['payment_date'],
                'amount_paid'    => $totalAmount,
                'principal_paid' => $data['principal_paid'],
                'interest_paid'  => $data['interest_paid'],
                'payment_method' => $payFromSavings ? 'Savings Wallet' : $data['payment_method'],
                'receipt_number' => $receiptNumber,
                'notes'          => $data['notes'] ?? null,
            ]);

            // Cash-funded repayments bring new cash in the door; wallet
            // repayments just move an existing liability (savings) down
            // against the loan portfolio — no cash line for those.
            $repaymentLines = [
                ['code' => $payFromSavings ? '2000' : '1000', 'debit' => $totalAmount, 'description' => $payFromSavings ? 'Debited from savings' : 'Cash received'],
                ['code' => '1100', 'credit' => $data['principal_paid'], 'description' => 'Principal repaid'],
                ['code' => '4000', 'credit' => $data['interest_paid'], 'description' => 'Interest income'],
            ];
            $repaymentEntry = JournalPoster::post($managerId, 'Loan repayment — receipt ' . $receiptNumber, 'loan_repayment', $repaymentLines, $receiptNumber);

            if ($repaymentEntry) {
                $newPayment->update(['journal_entry_id' => $repaymentEntry->id]);
            }

            if ($payFromSavings) {
                $savingsAccount->decrement('balance', $totalAmount);

                MfiTransaction::create([
                    'loan_manager_id' => $managerId,
                    'client_id' => $loan->client_id,
                    'mfi_account_id' => $savingsAccount->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $totalAmount,
                    'credit' => 0,
                    'debit' => $totalAmount,
                    'transaction_date' => $data['payment_date'],
                    'payment_method' => 'Savings Wallet',
                    'reference_number' => $receiptNumber,
                    'narration' => 'Loan repayment paid from savings (Receipt: ' . $receiptNumber . ')',
                ]);
            }

            // --- Compulsory savings auto-split ---
            // Only on cash-funded repayments: paying from savings and then
            // immediately re-depositing a slice back into that same wallet
            // would be a no-op.
            $compulsorySplit = 0;
            if ($isMfi && !$payFromSavings && $loan->mfi_loan_product_id) {
                $product = MfiProduct::find($loan->mfi_loan_product_id);
                $percent = $product ? $product->compulsory_savings_percent : 0;

                if ($percent > 0) {
                    $splitAmount = round($totalAmount * $percent / 100, 2);

                    if ($splitAmount > 0) {
                        $compulsoryAccount = self::resolveSavingsAccount($managerId, $loan->client_id);
                        $compulsoryAccount->increment('balance', $splitAmount);

                        MfiTransaction::create([
                            'loan_manager_id' => $managerId,
                            'client_id' => $loan->client_id,
                            'mfi_account_id' => $compulsoryAccount->id,
                            'transaction_type' => 'deposit',
                            'amount' => $splitAmount,
                            'credit' => $splitAmount,
                            'debit' => 0,
                            'transaction_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'narration' => 'Compulsory savings on loan repayment (Receipt: ' . $receiptNumber . ')',
                        ]);

                        JournalPoster::post($managerId, 'Compulsory savings top-up — receipt ' . $receiptNumber, 'compulsory_savings', [
                            ['code' => '1000', 'debit' => $splitAmount, 'description' => 'Cash received'],
                            ['code' => '2000', 'credit' => $splitAmount, 'description' => 'Compulsory savings top-up'],
                        ], $receiptNumber);

                        $compulsorySplit = $splitAmount;
                    }
                }
            }

            // --- Loan payoff + collateral release ---
            // Uses the same lien-release logic as LoanController's manual
            // status toggle, so a loan paid off through the normal
            // repayment screen also frees up any locked savings collateral.
            $totalDue = $loan->principalInterestDue();
            $paidSoFar = $loan->payments()->sum('amount_paid');

            $justPaidOff = false;
            if ($paidSoFar >= $totalDue && $loan->status !== 'paid') {
                if ($isMfi && $loan->collateral_locked > 0) {
                    self::releaseLoanCollateral($loan, $managerId);
                }
                $loan->status = 'paid';
                $loan->save();
                $justPaidOff = true;
            }

            return ['payment' => $newPayment, 'compulsory_split' => $compulsorySplit, 'loan' => $loan, 'just_paid_off' => $justPaidOff];
        });

        $loan = $result['loan'];
        $currency = \App\Models\LoanManager::find($managerId)?->currency_symbol ?? 'UGX';
        $newBalance = max(0, round($loan->principalInterestDue() - $loan->payments()->sum('amount_paid'), 2));

        NotificationService::notify(
            $managerId,
            'payment_received',
            'Payment received on loan #' . $loan->id,
            ($loan->client?->name ?? 'Client') . ' paid ' . number_format($result['payment']->amount_paid) . ' towards loan #' . $loan->id . '.',
            $loan->client_id,
            route('loans.show', $loan->id),
            'We received your payment of ' . $currency . ' ' . number_format($result['payment']->amount_paid) . ' on loan #' . $loan->id . '. Remaining balance: ' . $currency . ' ' . number_format($newBalance) . '.'
        );

        if ($result['just_paid_off']) {
            NotificationService::notify(
                $managerId,
                'loan_paid_off',
                'Loan #' . $loan->id . ' fully paid off',
                ($loan->client?->name ?? 'Client') . '\'s loan has been fully repaid.',
                $loan->client_id,
                route('loans.show', $loan->id),
                'Congratulations! Your loan #' . $loan->id . ' has been fully repaid. Thank you for banking with us.'
            );
        }

        return $result;
    }

    /**
     * Find the client's active savings account, or auto-provision a default
     * one if they don't have one yet (mirrors SavingsController's fallback
     * so the compulsory savings split always has somewhere to land).
     */
    private static function resolveSavingsAccount(int $managerId, int $clientId): MfiAccount
    {
        $account = MfiAccount::where('loan_manager_id', $managerId)
            ->where('client_id', $clientId)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if ($account) {
            return $account;
        }

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

        return MfiAccount::create([
            'loan_manager_id' => $managerId,
            'client_id' => $clientId,
            'mfi_product_id' => $productId,
            'account_number' => 'SAV-' . time() . rand(10, 99),
            'account_type' => 'savings',
            'balance' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Release the exact amount of collateral this loan locked, back onto the
     * client's savings account. Caller is responsible for saving $loan
     * afterwards.
     */
    private static function releaseLoanCollateral(Loan $loan, int $managerId): void
    {
        $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
            ->where('client_id', $loan->client_id)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if ($savingsAccount) {
            $release = min($loan->collateral_locked, $savingsAccount->lien_amount);
            $savingsAccount->decrement('lien_amount', $release);
        }

        $loan->collateral_locked = 0;
    }
}
