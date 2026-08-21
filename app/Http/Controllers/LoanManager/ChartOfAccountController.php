<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    /**
     * A reasonable starting chart of accounts for a SACCO/MFI. Seeded on
     * request rather than automatically, so a manager who wants a
     * different structure isn't stuck cleaning up accounts they didn't
     * ask for. Codes chosen so the natural sort order groups by type:
     * 1xxx assets, 2xxx liabilities, 3xxx equity, 4xxx income, 5xxx expense.
     */
    private const DEFAULT_ACCOUNTS = [
        ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset'],
        ['code' => '1010', 'name' => 'Bank Account', 'type' => 'asset'],
        ['code' => '1100', 'name' => 'Loan Portfolio (Principal Outstanding)', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Bank Placements (Treasury Fixed Deposits)', 'type' => 'asset'],
        ['code' => '1900', 'name' => 'Other Assets', 'type' => 'asset'],

        ['code' => '2000', 'name' => 'Member Savings Deposits', 'type' => 'liability'],
        ['code' => '2100', 'name' => 'Member Fixed Deposits', 'type' => 'liability'],
        ['code' => '2900', 'name' => 'Other Liabilities', 'type' => 'liability'],

        ['code' => '3000', 'name' => 'Share Capital', 'type' => 'equity'],
        ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity'],

        ['code' => '4000', 'name' => 'Interest Income on Loans', 'type' => 'income'],
        ['code' => '4100', 'name' => 'Loan Fees & Charges Income', 'type' => 'income'],
        ['code' => '4200', 'name' => 'Penalty Income', 'type' => 'income'],
        ['code' => '4900', 'name' => 'Other Income', 'type' => 'income'],

        ['code' => '5000', 'name' => 'Interest Expense on Savings', 'type' => 'expense'],
        ['code' => '5100', 'name' => 'Interest Expense on Fixed Deposits', 'type' => 'expense'],
        ['code' => '5200', 'name' => 'Operating Expenses', 'type' => 'expense'],
        ['code' => '5300', 'name' => 'Loan Loss Provision Expense', 'type' => 'expense'],
        ['code' => '5900', 'name' => 'Other Expenses', 'type' => 'expense'],
    ];

    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        $accounts = ChartOfAccount::where('loan_manager_id', $managerId)
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        $hasAny = ChartOfAccount::where('loan_manager_id', $managerId)->exists();

        return view('loan-manager.accounting.chart-of-accounts.index', compact('accounts', 'hasAny'));
    }

    public function create()
    {
        return view('loan-manager.accounting.chart-of-accounts.form', [
            'account' => new ChartOfAccount(),
            'isNew' => true,
        ]);
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $this->validateAccount($request, $managerId);

        ChartOfAccount::create([
            'loan_manager_id' => $managerId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'external_account_number' => $validated['external_account_number'] ?? null,
            'is_system' => false,
            'is_active' => true,
        ]);

        return redirect()->route('chart-of-accounts.index')->with('success', 'Account created.');
    }

    public function edit($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $account = ChartOfAccount::where('loan_manager_id', $managerId)->findOrFail($id);

        return view('loan-manager.accounting.chart-of-accounts.form', [
            'account' => $account,
            'isNew' => false,
        ]);
    }

    public function update(Request $request, $id)
    {
        $managerId = Auth::user()->loanManager->id;
        $account = ChartOfAccount::where('loan_manager_id', $managerId)->findOrFail($id);

        $validated = $this->validateAccount($request, $managerId, $account->id);

        // A system/default account's code and type are load-bearing for the
        // auto-posting logic that later work will wire up — only the name
        // and description are safe to let a manager change.
        if ($account->is_system) {
            $account->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'external_account_number' => $validated['external_account_number'] ?? null,
            ]);
        } else {
            $account->update($validated);
        }

        return redirect()->route('chart-of-accounts.index')->with('success', 'Account updated.');
    }

    /**
     * Deactivate/reactivate instead of deleting — same reasoning as
     * MfiProductController::toggle(): once an account has journal lines
     * against it, deleting it would orphan real financial history.
     */
    public function toggle($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $account = ChartOfAccount::where('loan_manager_id', $managerId)->findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);

        return back()->with('success', $account->is_active ? 'Account activated.' : 'Account deactivated.');
    }

    /**
     * One-click starting chart of accounts. Safe to run more than once —
     * skips any code that already exists for this tenant.
     */
    public function seedDefaults()
    {
        $managerId = Auth::user()->loanManager->id;

        $existingCodes = ChartOfAccount::where('loan_manager_id', $managerId)->pluck('code')->all();

        $created = 0;
        foreach (self::DEFAULT_ACCOUNTS as $defaultAccount) {
            if (in_array($defaultAccount['code'], $existingCodes, true)) {
                continue;
            }

            ChartOfAccount::create([
                'loan_manager_id' => $managerId,
                'code' => $defaultAccount['code'],
                'name' => $defaultAccount['name'],
                'type' => $defaultAccount['type'],
                'is_system' => true,
                'is_active' => true,
            ]);
            $created++;
        }

        $message = $created > 0
            ? "Added {$created} standard account(s)."
            : 'Your chart of accounts already has all the standard accounts.';

        return back()->with('success', $message);
    }

    private function validateAccount(Request $request, int $managerId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('chart_of_accounts')->where('loan_manager_id', $managerId)->ignore($ignoreId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'description' => 'nullable|string|max:1000',
            'bank_name' => 'nullable|string|max:255',
            'external_account_number' => 'nullable|string|max:100',
        ]);
    }
}
