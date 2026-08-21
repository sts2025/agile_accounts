<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JournalEntryController extends Controller
{
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        // Aliased away from 'total_debit' — that name collides with the
        // JournalEntry::getTotalDebitAttribute() accessor, which would
        // silently shadow this pre-computed sum and trigger an N+1 lazy
        // load of every entry's lines instead.
        $entries = JournalEntry::where('loan_manager_id', $managerId)
            ->withSum('lines as debit_sum', 'debit')
            ->with('createdBy')
            ->latest('entry_date')
            ->latest('id')
            ->paginate(20);

        return view('loan-manager.accounting.journal-entries.index', compact('entries'));
    }

    public function create()
    {
        $managerId = Auth::user()->loanManager->id;

        $accounts = ChartOfAccount::where('loan_manager_id', $managerId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()->route('chart-of-accounts.index')
                ->with('error', 'Set up your chart of accounts first — you need at least two active accounts before you can post a journal entry.');
        }

        return view('loan-manager.accounting.journal-entries.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'reference_no' => 'nullable|string|max:255',
            'narration' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:2',
            'lines.*.chart_of_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('loan_manager_id', $managerId),
            ],
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        // Drop blank rows (both amounts empty/zero) before validating the total.
        $lines = collect($validated['lines'])->filter(function ($line) {
            return (float) ($line['debit'] ?? 0) > 0 || (float) ($line['credit'] ?? 0) > 0;
        })->values();

        if ($lines->count() < 2) {
            return back()->with('error', 'A journal entry needs at least two lines with an amount.')->withInput();
        }

        foreach ($lines as $line) {
            if ((float) ($line['debit'] ?? 0) > 0 && (float) ($line['credit'] ?? 0) > 0) {
                return back()->with('error', 'A single line can\'t have both a debit and a credit — split it into two lines.')->withInput();
            }
        }

        $totalDebit = round($lines->sum(fn ($l) => (float) ($l['debit'] ?? 0)), 2);
        $totalCredit = round($lines->sum(fn ($l) => (float) ($l['credit'] ?? 0)), 2);

        if ($totalDebit !== $totalCredit) {
            return back()->with('error', "This entry doesn't balance — debits total " . number_format($totalDebit, 2) . " but credits total " . number_format($totalCredit, 2) . '.')->withInput();
        }

        DB::transaction(function () use ($validated, $lines, $managerId) {
            $entry = JournalEntry::create([
                'loan_manager_id' => $managerId,
                'entry_date' => $validated['entry_date'],
                'reference_no' => $validated['reference_no'] ?? null,
                'narration' => $validated['narration'] ?? null,
                'created_by' => Auth::id(),
                'source' => 'manual',
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('journal-entries.index')->with('success', 'Journal entry posted.');
    }

    public function show($id)
    {
        $managerId = Auth::user()->loanManager->id;

        $entry = JournalEntry::where('loan_manager_id', $managerId)
            ->with(['lines.account', 'createdBy', 'reverses', 'reversal'])
            ->findOrFail($id);

        return view('loan-manager.accounting.journal-entries.show', compact('entry'));
    }

    /**
     * Audit-safe correction: post a brand-new entry with every line's
     * debit/credit swapped, rather than editing or deleting the original.
     * This is the same "never silently mutate financial history" principle
     * used elsewhere in the app (see PaymentController::destroy()).
     */
    public function reverse($id)
    {
        $managerId = Auth::user()->loanManager->id;

        $entry = JournalEntry::where('loan_manager_id', $managerId)->with('lines')->findOrFail($id);

        if ($entry->is_reversed) {
            return back()->with('error', 'This entry has already been reversed.');
        }

        if ($entry->reverses_journal_entry_id) {
            return back()->with('error', 'A reversal entry can\'t itself be reversed — post a fresh correcting entry instead.');
        }

        DB::transaction(function () use ($entry, $managerId) {
            $reversal = JournalEntry::create([
                'loan_manager_id' => $managerId,
                'entry_date' => now()->toDateString(),
                'reference_no' => $entry->reference_no,
                'narration' => 'Reversal of entry #' . $entry->id . (($entry->narration) ? ' — ' . $entry->narration : ''),
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
        });

        return redirect()->route('journal-entries.index')->with('success', 'Entry reversed.');
    }
}
