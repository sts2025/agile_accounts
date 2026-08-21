<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\Auth;

/**
 * Shared helper for auto-posting double-entry journal entries from the
 * various transaction-creating flows (savings, shares, fixed deposits,
 * loan repayments) into the Chart of Accounts / General Journal. Loan
 * disbursement and write-off already had their own private
 * postXJournalEntry() methods on LoanController before this existed —
 * those are left as-is; this service exists so the newer integrations
 * don't each reinvent the same lookup-account/create-entry/create-lines
 * boilerplate.
 *
 * Posting is always best-effort: if a manager hasn't loaded the standard
 * chart of accounts yet (or has deactivated one of the accounts a line
 * needs), post() returns null and posts nothing rather than throwing —
 * the underlying MfiTransaction/Payment/etc. this is attached to must
 * never be blocked or rolled back just because bookkeeping couldn't run.
 */
class JournalPoster
{
    /**
     * Post a multi-line journal entry.
     *
     * @param array<int, array{code: string, debit?: float, credit?: float, description?: string}> $lines
     */
    public static function post(int $managerId, string $narration, string $source, array $lines, ?string $referenceNo = null): ?JournalEntry
    {
        if (count($lines) < 2) {
            return null;
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $accounts = [];

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            // A zero-amount line is meaningless (and would still need a
            // valid account below) — skip it rather than posting noise.
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $account = ChartOfAccount::where('loan_manager_id', $managerId)
                ->where('code', $line['code'])
                ->where('is_active', true)
                ->first();

            if (!$account) {
                return null;
            }

            $accounts[] = ['account' => $account, 'debit' => $debit, 'credit' => $credit, 'description' => $line['description'] ?? null];
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (count($accounts) < 2) {
            return null;
        }

        if (abs(round($totalDebit, 2) - round($totalCredit, 2)) > 0.01) {
            return null;
        }

        $entry = JournalEntry::create([
            'loan_manager_id' => $managerId,
            'entry_date' => now()->toDateString(),
            'reference_no' => $referenceNo,
            'narration' => $narration,
            'created_by' => Auth::id(),
            'source' => $source,
        ]);

        foreach ($accounts as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $line['account']->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'description' => $line['description'],
            ]);
        }

        return $entry;
    }

    /**
     * Programmatic version of JournalEntryController::reverse() — posts a
     * brand-new entry with every line's debit/credit swapped rather than
     * editing or deleting the original, for automated reversals (e.g. a
     * loan repayment being deleted). Returns null (posts nothing) if the
     * entry is missing, already reversed, or is itself a reversal.
     */
    public static function reverse(?JournalEntry $entry, string $narrationPrefix = 'Reversal'): ?JournalEntry
    {
        if (!$entry || $entry->is_reversed || $entry->reverses_journal_entry_id) {
            return null;
        }

        $entry->loadMissing('lines');

        $reversal = JournalEntry::create([
            'loan_manager_id' => $entry->loan_manager_id,
            'entry_date' => now()->toDateString(),
            'reference_no' => $entry->reference_no,
            'narration' => $narrationPrefix . ' of entry #' . $entry->id . ($entry->narration ? ' — ' . $entry->narration : ''),
            'created_by' => Auth::id(),
            'source' => $entry->source === 'manual' ? 'manual' : $entry->source . '_reversal',
            'reverses_journal_entry_id' => $entry->id,
        ]);

        foreach ($entry->lines as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $reversal->id,
                'chart_of_account_id' => $line->chart_of_account_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'description' => 'Reversal: ' . ($line->description ?? ''),
            ]);
        }

        $entry->update(['is_reversed' => true]);

        return $reversal;
    }
}
