<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller; 
use App\Models\Loan;
use App\Models\Client;
use App\Models\Guarantor;
use App\Models\Collateral;
use App\Models\MfiAccount;
use App\Models\MfiProduct;
use App\Models\ClientGroup;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LoanReschedule;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\RepaymentScheduleGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

// --- NEW IMPORTS FOR LARAVEL 11/12 MIDDLEWARE ---
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanController extends Controller implements HasMiddleware
{
    /**
     * Default savings-led lending rule: a client must have this fraction of
     * the requested principal sitting free (unlocked) in an active savings
     * account before a loan can be disbursed. Only enforced for tenants that
     * have upgraded to the Microfinance engine (loan_managers.is_mfi = true);
     * standard loan managers keep the original, savings-free lending flow.
     *
     * TODO: move this to a per-product setting (mfi_products.rules) once the
     * Product Settings screen from the roadmap is built, so each manager can
     * configure their own multiplier instead of a hardcoded 30%.
     */
    const COLLATERAL_RATIO = 0.30;

    /**
     * Get the middleware that should be assigned to the controller.
     * This replaces the old $this->middleware() in the constructor.
     *
     * NOTE: this used to also require an 'elevated' middleware on
     * update/destroy, but that alias was never registered in
     * bootstrap/app.php and the password-gate feature it was meant to
     * trigger (ElevateController + /elevate routes) was never wired up
     * correctly either — every PUT/DELETE to /loans/{loan} was throwing a
     * fatal "middleware alias does not exist" error. Removed rather than
     * completed; loan edit/delete now use the same auth as everything else.
     */
    public static function middleware(): array
    {
        return [
            //
        ];
    }

    /**
     * Display the list of loans for the manager.
     */
    public function index(Request $request)
    {
        $loanManager = Auth::user()->loanManager;
        
        $query = $loanManager->loans()->with(['client', 'payments', 'clientGroup']);

        // Search by Client Name
        if ($search = $request->input('search')) {
            $query->whereHas('client', function($subQuery) use ($search) {
                $searchTerm = strtolower($search);
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        // Sidebar Status Filters
        if ($filter = $request->input('filter')) {
            if ($filter === 'completed') {
                $query->where('status', 'paid');
            } elseif ($filter === 'active') {
                $query->where('status', 'active');
            } elseif ($filter === 'defaulted') {
                $query->where('status', 'defaulted');
            } elseif ($filter === 'pending') {
                $query->where('approval_status', 'pending');
            } elseif ($filter === 'approved') {
                $query->where('approval_status', 'approved');
            }
        }
        
        $currency_symbol = $loanManager->currency_symbol ?? 'UGX'; 
        $loans = $query->latest()->paginate(10); 
        
        return view('loan-manager.loans.index', compact('loans', 'currency_symbol'));
    }

    /**
     * Show create loan form.
     */
    public function create(Request $request)
    {
        $clients = Auth::user()->loanManager->clients()
                        ->orderBy('created_at', 'desc')
                        ->get();

        // Only MFI-upgraded managers deal in savings-backed loan products.
        $loanProducts = collect();
        if (optional(Auth::user()->loanManager)->is_mfi) {
            $loanProducts = MfiProduct::where('loan_manager_id', Auth::user()->loanManager->id)
                ->where('product_type', 'loan')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Group lending: list of groups for the optional "Client Group"
        // picker, and which group (if any) to preselect — e.g. when arriving
        // via the "Issue Group Loan" button on a group's page.
        $clientGroups = ClientGroup::where('loan_manager_id', Auth::user()->loanManager->id)
            ->with('members')
            ->orderBy('name')
            ->get();
        $selectedGroupId = $request->query('group');

        return view('loan-manager.loans.create', compact('clients', 'loanProducts', 'clientGroups', 'selectedGroupId'));
    }

    /**
     * Store a new loan record.
     */
    public function store(Request $request)
    {
        $loanManagerId = Auth::user()->loanManager->id;

        $validatedData = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('loan_manager_id', $loanManagerId)],
            'client_group_id' => ['nullable', Rule::exists('client_groups', 'id')->where('loan_manager_id', $loanManagerId)],
            'mfi_loan_product_id' => ['nullable', Rule::exists('mfi_products', 'id')->where('loan_manager_id', $loanManagerId)->where('product_type', 'loan')],
            'principal_amount' => 'required|numeric|min:100',
            'processing_fee' => 'nullable|numeric|min:0', 
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_method' => 'nullable|string|in:flat,reducing_balance',
            'term' => 'required|integer|min:1',
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date', 

            // Guarantor validation
            'guarantor_first_name' => 'nullable|string|max:255',
            'guarantor_last_name' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_phone_number' => 'required_with:guarantor_first_name|string|max:20',
            'guarantor_address' => 'required_with:guarantor_first_name|string|max:255',
            'guarantor_occupation' => 'nullable|string|max:255',
            'guarantor_relationship' => 'required_with:guarantor_first_name|string|max:100',

            // Collateral validation
            'collateral_type' => 'nullable|string|max:100',
            'collateral_description' => 'required_with:collateral_type|string',
            'collateral_valuation_amount' => 'required_with:collateral_type|numeric|min:1',
        ]);

        $client = Client::find($validatedData['client_id']);
        if ($client && $client->is_blacklisted) {
            return back()->with('error', $client->name . ' is blacklisted and cannot receive new loans.' . ($client->blacklist_reason ? ' Reason: ' . $client->blacklist_reason : ''))->withInput();
        }

        try {
            DB::transaction(function () use ($validatedData, $request, $loanManagerId, $client) {
                $loanCount = Loan::where('loan_manager_id', $loanManagerId)->count();

                $loan = Loan::create([
                    'client_id' => $validatedData['client_id'],
                    'client_group_id' => $validatedData['client_group_id'] ?? null,
                    'loan_manager_id' => $loanManagerId,
                    // Loans inherit their client's branch — a loan doesn't
                    // move offices independently of the person who took it.
                    'branch_id' => $client->branch_id ?? null,
                    'mfi_loan_product_id' => $validatedData['mfi_loan_product_id'] ?? null,
                    'principal_amount' => $validatedData['principal_amount'],
                    'processing_fee' => $validatedData['processing_fee'] ?? 0,
                    'interest_rate' => $validatedData['interest_rate'],
                    'interest_method' => $validatedData['interest_method'] ?? 'flat',
                    'term' => $validatedData['term'],
                    'repayment_frequency' => $validatedData['repayment_frequency'],
                    'start_date' => $validatedData['start_date'],
                    // New loans start life awaiting approval — Loan
                    // Application, Approval, and Disbursement are three
                    // separate steps (see approve()/reject()/disburse()
                    // below), not one instant action. approval_status
                    // drives that pipeline; status stays in lockstep as
                    // 'pending' until disbursement, then becomes the usual
                    // active/paid/defaulted lifecycle.
                    'status' => 'pending',
                    'approval_status' => 'pending',
                    'reference_id' => 'LN-' . str_pad($loanCount + 1, 4, '0', STR_PAD_LEFT),
                ]);

                // Savings-led lending: lock collateral from the client's MFI
                // savings account before the loan is allowed to disburse.
                // Only applies to loan managers who have upgraded to MFI —
                // standard tenants are unaffected.
                if (optional(Auth::user()->loanManager)->is_mfi) {
                    $this->lockLoanCollateral($loan, $validatedData['mfi_loan_product_id'] ?? null);
                }

                if ($request->filled('guarantor_first_name')) {
                    $loan->guarantors()->create([
                        'first_name' => $validatedData['guarantor_first_name'],
                        'last_name' => $validatedData['guarantor_last_name'],
                        'phone_number' => $validatedData['guarantor_phone_number'],
                        'address' => $validatedData['guarantor_address'],
                        'occupation' => $validatedData['guarantor_occupation'] ?? null,
                        'relationship_to_borrower' => $validatedData['guarantor_relationship'],
                    ]);
                }

                if ($request->filled('collateral_type')) {
                    $loan->collaterals()->create([
                        'collateral_type' => $validatedData['collateral_type'],
                        'description' => $validatedData['collateral_description'],
                        'valuation_amount' => $validatedData['collateral_valuation_amount'],
                    ]);
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('loans.index')->with('success', 'New loan created and recorded successfully!');
    }

    /**
     * Lock savings collateral for a newly created loan (savings-led lending).
     * Throws if the client has no active savings account or insufficient
     * unlocked funds — the caller's DB::transaction will roll the whole loan
     * creation back, so a loan is never created without its required lien.
     */
    private function lockLoanCollateral(Loan $loan, $loanProductId = null): void
    {
        $managerId = Auth::user()->loanManager->id; // mfi_accounts.loan_manager_id stores loan_managers.id

        $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
            ->where('client_id', $loan->client_id)
            ->where('account_type', 'savings')
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        // Use the selected loan product's configured collateral ratio if one
        // was picked (see Product Settings); otherwise fall back to the
        // built-in default.
        $collateralRatio = self::COLLATERAL_RATIO;
        if ($loanProductId) {
            $product = MfiProduct::where('loan_manager_id', $managerId)
                ->where('id', $loanProductId)
                ->where('product_type', 'loan')
                ->first();
            if ($product) {
                $collateralRatio = $product->collateral_ratio;
            }
        }

        $requiredCollateral = round($loan->principal_amount * $collateralRatio, 2);

        if (!$savingsAccount) {
            throw new \Exception("This client has no active savings account. Under your Microfinance plan, a loan requires at least UGX " . number_format($requiredCollateral) . " (" . number_format($collateralRatio * 100, 0) . "% of principal) held in savings as collateral before it can be disbursed.");
        }

        $availableBalance = $savingsAccount->balance - $savingsAccount->lien_amount;

        if ($availableBalance < $requiredCollateral) {
            throw new \Exception("Insufficient savings collateral. This loan requires UGX " . number_format($requiredCollateral) . " available, but the client only has UGX " . number_format($availableBalance) . " unlocked in savings.");
        }

        $savingsAccount->increment('lien_amount', $requiredCollateral);
        $loan->collateral_locked = $requiredCollateral;
        $loan->save();
    }

    /**
     * Update loan status via AJAX.
     */
    public function updateStatus(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'new_status' => 'required|string|in:active,paid,defaulted',
        ]);

        if ($validated['new_status'] === 'active' && $loan->approval_status !== 'disbursed') {
            return response()->json(['message' => 'This loan must be approved and disbursed before it can be marked active. Use the Approve / Disburse actions instead.'], 422);
        }

        try {
            DB::transaction(function () use ($validated, $loan) {
                // Releasing collateral: only when a loan that actually had
                // savings locked against it becomes fully paid. Defaulted
                // loans deliberately keep their lien in place — that's the
                // point of collateral — until a separate write-off/recovery
                // action is built.
                if ($validated['new_status'] === 'paid' && $loan->collateral_locked > 0) {
                    $this->releaseLoanCollateral($loan);
                }

                $loan->status = $validated['new_status'];
                $loan->save();
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Loan status updated successfully.',
            'status' => $loan->status,
            'loan_id' => $loan->id
        ]);
    }

    /**
     * Release the exact amount of collateral this loan locked, back onto the
     * client's savings account. Uses the loan's own collateral_locked value
     * (not the account's full lien_amount) so a client's other concurrent
     * loans keep their collateral untouched.
     */
    private function releaseLoanCollateral(Loan $loan): void
    {
        $managerId = Auth::user()->loanManager->id; // mfi_accounts.loan_manager_id stores loan_managers.id

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
        $loan->save();
    }

    /**
     * Step 2 of Application -> Approval -> Disbursement: approve a pending
     * application. Does NOT move any money or change `status` — the loan
     * only becomes 'active' once it's actually disbursed via disburse().
     */
    public function approve(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'pending') {
            return back()->with('error', 'Only a pending application can be approved.');
        }

        $loan->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        NotificationService::notify(
            $loan->loan_manager_id,
            'loan_approved',
            'Loan #' . $loan->id . ' approved',
            ($loan->client?->name ?? 'Client') . '\'s loan application was approved and is awaiting disbursement.',
            $loan->client_id,
            route('loans.show', $loan->id),
            'Good news! Your loan application (#' . $loan->id . ') has been approved. We\'ll be in touch about disbursement.'
        );

        return back()->with('success', 'Loan approved. It still needs to be disbursed before it goes active.');
    }

    /**
     * Reject a pending application and release any collateral that was
     * locked at application time, since the loan will never disburse now.
     */
    public function reject(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'pending') {
            return back()->with('error', 'Only a pending application can be rejected.');
        }

        $validated = $request->validate([
            'rejection_note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($loan, $validated) {
            if ($loan->collateral_locked > 0) {
                $this->releaseLoanCollateral($loan);
            }

            $loan->update([
                'approval_status' => 'rejected',
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_note' => $validated['rejection_note'] ?? null,
            ]);
        });

        NotificationService::notify(
            $loan->loan_manager_id,
            'loan_rejected',
            'Loan #' . $loan->id . ' rejected',
            ($loan->client?->name ?? 'Client') . '\'s loan application was rejected.' . (!empty($validated['rejection_note']) ? ' Reason: ' . $validated['rejection_note'] : ''),
            $loan->client_id,
            route('loans.show', $loan->id)
        );

        return back()->with('success', 'Loan application rejected.');
    }

    /**
     * Step 3: actually disburse an approved loan — this is the point the
     * loan goes 'active' and money is understood to have left the door.
     * If the tenant has a chart of accounts set up, also auto-posts the
     * standard Dr Loan Portfolio / Cr Cash journal entry so the General
     * Journal and reports reflect it without a manual entry. Skipped
     * quietly (not an error) for tenants who haven't set up accounting yet.
     */
    public function disburse(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'approved') {
            return back()->with('error', 'This loan must be approved before it can be disbursed.');
        }

        $managerId = Auth::user()->loanManager->id;

        // Top-up/restructure: this loan is replacing an older one — settle
        // that loan's remaining balance out of the new proceeds instead of
        // the usual "full principal out the door" disbursement.
        if ($loan->replaces_loan_id) {
            return $this->disburseTopUp($loan, $managerId);
        }

        DB::transaction(function () use ($loan, $managerId) {
            $journalEntry = $this->postDisbursementJournalEntry($loan, $managerId);

            $loan->update([
                'approval_status' => 'disbursed',
                'status' => 'active',
                'disbursement_journal_entry_id' => $journalEntry?->id,
            ]);

            RepaymentScheduleGenerator::generate($loan);
        });

        NotificationService::notify(
            $loan->loan_manager_id,
            'loan_disbursed',
            'Loan #' . $loan->id . ' disbursed',
            ($loan->client?->name ?? 'Client') . '\'s loan of ' . number_format($loan->principal_amount) . ' was disbursed and is now active.',
            $loan->client_id,
            route('loans.show', $loan->id),
            'Your loan #' . $loan->id . ' of ' . \App\Models\LoanManager::getCurrency() . ' ' . number_format($loan->principal_amount) . ' has been disbursed. Repayments start ' . \Carbon\Carbon::parse($loan->start_date)->format('d M Y') . '.'
        );

        return back()->with('success', 'Loan disbursed and marked active.' . ($loan->disbursement_journal_entry_id ? ' Journal entry posted.' : ''));
    }

    /**
     * Show the top-up/restructure form for an existing disbursed loan: the
     * client's old balance is netted off the new loan's proceeds at
     * disbursement instead of requiring a full separate repayment first.
     */
    public function topUp(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'disbursed' || in_array($loan->status, ['paid', 'written_off'], true)) {
            return back()->with('error', 'Only an active, disbursed loan with a remaining balance can be topped up.');
        }

        $outstanding = $loan->principalInterestDue() - $loan->payments()->sum('amount_paid');

        return view('loan-manager.loans.top-up', [
            'loan' => $loan,
            'outstandingBalance' => max(0, round($outstanding, 2)),
        ]);
    }

    /**
     * Create the replacement loan application. Like any other loan it still
     * goes through the normal approve → disburse pipeline; the actual
     * netting-off against the old loan happens in disburseTopUp() at
     * disbursement time (see disburse()), not here, since the old balance
     * can keep moving between now and whenever it's actually approved.
     */
    public function storeTopUp(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        $loanManagerId = $loan->loan_manager_id;

        if ($loan->approval_status !== 'disbursed' || in_array($loan->status, ['paid', 'written_off'], true)) {
            return back()->with('error', 'Only an active, disbursed loan with a remaining balance can be topped up.');
        }

        $validated = $request->validate([
            'principal_amount' => 'required|numeric|min:100',
            'processing_fee' => 'nullable|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_method' => 'nullable|string|in:flat,reducing_balance',
            'term' => 'required|integer|min:1',
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date',
        ]);

        $outstanding = $loan->principalInterestDue() - $loan->payments()->sum('amount_paid');

        if ($validated['principal_amount'] < $outstanding) {
            return back()->with('error', 'The new principal (' . number_format($validated['principal_amount']) . ') must be at least the old loan\'s outstanding balance (' . number_format($outstanding) . ') — a top-up can\'t hand out less than what\'s already owed.')->withInput();
        }

        $newLoan = DB::transaction(function () use ($validated, $loan, $loanManagerId) {
            $loanCount = Loan::where('loan_manager_id', $loanManagerId)->count();

            return Loan::create([
                'client_id' => $loan->client_id,
                'client_group_id' => $loan->client_group_id,
                'loan_manager_id' => $loanManagerId,
                'branch_id' => $loan->branch_id,
                'replaces_loan_id' => $loan->id,
                'mfi_loan_product_id' => $loan->mfi_loan_product_id,
                'principal_amount' => $validated['principal_amount'],
                'processing_fee' => $validated['processing_fee'] ?? 0,
                'interest_rate' => $validated['interest_rate'],
                'interest_method' => $validated['interest_method'] ?? 'flat',
                'term' => $validated['term'],
                'repayment_frequency' => $validated['repayment_frequency'],
                'start_date' => $validated['start_date'],
                'status' => 'pending',
                'approval_status' => 'pending',
                'reference_id' => 'LN-' . str_pad($loanCount + 1, 4, '0', STR_PAD_LEFT),
            ]);
        });

        return redirect()->route('loans.show', $newLoan->id)->with('success', 'Top-up application created (Ref: ' . $newLoan->reference_id . '). It still needs approval and disbursement like any other loan — the old loan\'s balance will be settled automatically when this one is disbursed.');
    }

    /**
     * Undo a disbursement made in error. Only allowed before any repayment
     * has been recorded against the loan, to avoid unwinding a loan that
     * already has real repayment activity built on top of it.
     */
    public function reverseDisbursement(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'disbursed') {
            return back()->with('error', 'This loan hasn\'t been disbursed.');
        }

        if ($loan->payments()->exists()) {
            return back()->with('error', 'This loan already has repayments recorded against it — reversing disbursement is no longer safe. Use Write Off instead if it needs to come off the books.');
        }

        if ($loan->replaces_loan_id) {
            return back()->with('error', 'This loan was disbursed as a top-up of Loan #' . $loan->replaces_loan_id . ', which was already settled and closed as part of that disbursement. Reversing it here won\'t automatically reopen the old loan — undo that manually first if you really need to unwind this.');
        }

        DB::transaction(function () use ($loan) {
            if ($loan->disbursement_journal_entry_id) {
                $originalEntry = JournalEntry::with('lines')->find($loan->disbursement_journal_entry_id);
                if ($originalEntry && !$originalEntry->is_reversed) {
                    $reversal = JournalEntry::create([
                        'loan_manager_id' => $loan->loan_manager_id,
                        'entry_date' => now()->toDateString(),
                        'reference_no' => $originalEntry->reference_no,
                        'narration' => 'Reversal of loan disbursement — entry #' . $originalEntry->id,
                        'created_by' => Auth::id(),
                        'source' => 'loan_disbursement_reversal',
                        'reverses_journal_entry_id' => $originalEntry->id,
                    ]);

                    foreach ($originalEntry->lines as $line) {
                        JournalEntryLine::create([
                            'journal_entry_id' => $reversal->id,
                            'chart_of_account_id' => $line->chart_of_account_id,
                            'debit' => $line->credit,
                            'credit' => $line->debit,
                            'description' => 'Reversal: ' . ($line->description ?? ''),
                        ]);
                    }

                    $originalEntry->update(['is_reversed' => true]);
                }
            }

            $loan->update([
                'approval_status' => 'approved',
                'status' => 'pending',
                'disbursement_journal_entry_id' => null,
            ]);

            // The repayment schedule was generated off this (now-undone)
            // disbursement's start date — clear it rather than leave a
            // stale schedule sitting against a loan that isn't active.
            $loan->repaymentSchedules()->delete();
        });

        return back()->with('success', 'Disbursement reversed. Loan is back to "approved, awaiting disbursement".');
    }

    /**
     * Write off a loan as uncollectable bad debt. Unlike the other status
     * changes, this is deliberately final-feeling (no "un-write-off"
     * action) — it's meant for genuinely dead debt, not a mistake to
     * undo. The loan's full history (payments, schedule) stays intact;
     * only its status changes, so it drops out of the "active" reports
     * and the outstanding balance is posted as a loan-loss expense.
     * Any remaining locked collateral is released — a decision has
     * already been made that this debt isn't being pursued further, so
     * there's no reason to keep the client's savings tied up over it;
     * seizing collateral toward the balance is a separate manual step
     * outside this action if that's how a given write-off is handled.
     */
    public function writeOff(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'disbursed') {
            return back()->with('error', 'Only a disbursed loan can be written off.');
        }

        if (in_array($loan->status, ['paid', 'written_off'], true)) {
            return back()->with('error', 'This loan is already ' . $loan->status . '.');
        }

        $outstanding = $loan->balance();

        if ($outstanding <= 0) {
            return back()->with('error', 'This loan has no outstanding balance to write off.');
        }

        $validated = $request->validate([
            'write_off_reason' => 'nullable|string|max:1000',
        ]);

        $managerId = Auth::user()->loanManager->id;

        DB::transaction(function () use ($loan, $validated, $outstanding, $managerId) {
            if ($loan->collateral_locked > 0) {
                $this->releaseLoanCollateral($loan);
            }

            $journalEntry = $this->postWriteOffJournalEntry($loan, $outstanding, $managerId);

            $loan->update([
                'status' => 'written_off',
                'write_off_reason' => $validated['write_off_reason'] ?? null,
                'written_off_by' => Auth::id(),
                'written_off_at' => now(),
                'write_off_journal_entry_id' => $journalEntry?->id,
            ]);
        });

        NotificationService::notify(
            $loan->loan_manager_id,
            'loan_written_off',
            'Loan #' . $loan->id . ' written off',
            ($loan->client?->name ?? 'Client') . '\'s loan was written off. Outstanding balance of ' . number_format($outstanding) . ' recorded as a loss.',
            $loan->client_id,
            route('loans.show', $loan->id)
        );

        return back()->with('success', 'Loan written off. Outstanding balance of ' . number_format($outstanding) . ' recorded as a loss.');
    }

    /**
     * Dr Loan Loss Provision Expense (5300) / Cr Loan Portfolio (1100) for
     * the outstanding balance being written off. Same graceful skip as
     * postDisbursementJournalEntry() if those accounts aren't set up.
     */
    private function postWriteOffJournalEntry(Loan $loan, float $outstanding, int $managerId): ?JournalEntry
    {
        $lossExpense = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '5300')->where('is_active', true)->first();
        $loanPortfolio = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '1100')->where('is_active', true)->first();

        if (!$lossExpense || !$loanPortfolio) {
            return null;
        }

        $entry = JournalEntry::create([
            'loan_manager_id' => $managerId,
            'entry_date' => now()->toDateString(),
            'reference_no' => $loan->reference_id,
            'narration' => 'Loan write-off — ' . ($loan->client->name ?? 'client #' . $loan->client_id),
            'created_by' => Auth::id(),
            'source' => 'loan_write_off',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $lossExpense->id,
            'debit' => $outstanding,
            'credit' => 0,
            'description' => 'Written off as bad debt',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $loanPortfolio->id,
            'debit' => 0,
            'credit' => $outstanding,
            'description' => 'Loan portfolio reduction',
        ]);

        return $entry;
    }

    /**
     * Reschedule / refinance a disbursed loan: change its term, interest
     * rate, repayment frequency, and/or restart the repayment schedule
     * from a new date. The loan's own row is updated (so the rest of the
     * app — repayment schedule generation, reports — keeps working off a
     * single current set of terms), but a LoanReschedule row is written
     * first, capturing exactly what the terms were before, so nothing is
     * silently lost. Principal amount is deliberately not editable here —
     * see the loan_reschedules migration for why.
     */
    public function reschedule(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'disbursed' || in_array($loan->status, ['paid', 'written_off'], true)) {
            return back()->with('error', 'Only an active, disbursed loan can be rescheduled.');
        }

        $validated = $request->validate([
            'interest_rate' => 'required|numeric|min:0|max:100',
            'term' => 'required|integer|min:1',
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($loan, $validated) {
            LoanReschedule::create([
                'loan_id' => $loan->id,
                'loan_manager_id' => $loan->loan_manager_id,
                'old_interest_rate' => $loan->interest_rate,
                'old_term' => $loan->term,
                'old_repayment_frequency' => $loan->repayment_frequency,
                'old_start_date' => $loan->start_date,
                'new_interest_rate' => $validated['interest_rate'],
                'new_term' => $validated['term'],
                'new_repayment_frequency' => $validated['repayment_frequency'],
                'new_start_date' => $validated['start_date'],
                'reason' => $validated['reason'] ?? null,
                'rescheduled_by' => Auth::id(),
            ]);

            $loan->update([
                'interest_rate' => $validated['interest_rate'],
                'term' => $validated['term'],
                'repayment_frequency' => $validated['repayment_frequency'],
                'start_date' => $validated['start_date'],
            ]);

            RepaymentScheduleGenerator::generate($loan->fresh());
        });

        return redirect()->route('loans.show', $loan->id)->with('success', 'Loan rescheduled. Previous terms are kept on record below.');
    }

    /**
     * Read-only view of a loan's installment-by-installment repayment
     * schedule. Status per row is computed live from cumulative
     * scheduled-vs-paid rather than trusting the stored 'status' column on
     * each RepaymentSchedule row — a partial payment doesn't cleanly mark
     * one installment "paid" and leave the rest "pending", so recomputing
     * from actual payment totals avoids that row ever going stale.
     */
    public function schedule(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        $installments = $loan->repaymentSchedules()->orderBy('installment_number')->get();
        $totalPaid = $loan->payments()->sum('amount_paid');

        $today = now()->toDateString();
        $cumulativeDue = 0.0;
        $rows = [];

        foreach ($installments as $installment) {
            $previousCumulativeDue = $cumulativeDue;
            $cumulativeDue += (float) $installment->amount;

            if ($totalPaid >= $cumulativeDue - 0.01) {
                $status = 'Paid';
            } elseif ($totalPaid > $previousCumulativeDue + 0.01) {
                $status = 'Partially Paid';
            } elseif ($installment->due_date->toDateString() < $today) {
                $status = 'Overdue';
            } else {
                $status = 'Upcoming';
            }

            $rows[] = [
                'installment' => $installment,
                'cumulative_due' => $cumulativeDue,
                'status' => $status,
            ];
        }

        return view('loan-manager.loans.schedule', [
            'loan' => $loan,
            'rows' => $rows,
            'totalPaid' => $totalPaid,
            'totalScheduled' => $cumulativeDue,
        ]);
    }

    /**
     * Dr Loan Portfolio (1100) / Cr Cash on Hand (1000) using whatever the
     * tenant has actually named those codes as (falls back gracefully if
     * they've renamed/removed the standard accounts — just skips posting
     * rather than guessing or erroring the whole disbursement out).
     */
    private function postDisbursementJournalEntry(Loan $loan, int $managerId): ?JournalEntry
    {
        $loanPortfolio = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '1100')->where('is_active', true)->first();
        $cash = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '1000')->where('is_active', true)->first();

        if (!$loanPortfolio || !$cash) {
            return null;
        }

        $entry = JournalEntry::create([
            'loan_manager_id' => $managerId,
            'entry_date' => now()->toDateString(),
            'reference_no' => $loan->reference_id,
            'narration' => 'Loan disbursement — ' . ($loan->client->name ?? 'client #' . $loan->client_id),
            'created_by' => Auth::id(),
            'source' => 'loan_disbursement',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $loanPortfolio->id,
            'debit' => $loan->principal_amount,
            'credit' => 0,
            'description' => 'Principal disbursed',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $cash->id,
            'debit' => 0,
            'credit' => $loan->principal_amount,
            'description' => 'Cash out',
        ]);

        return $entry;
    }

    /**
     * Disbursement for a loan created via topUp()/storeTopUp(): instead of
     * paying the full new principal out in cash, the old loan's remaining
     * principal + interest is netted off first, and only the difference
     * (if any) actually goes out as cash. The old loan is settled with a
     * real Payment row (method "Loan Top-Up") so its own history/receipts
     * show exactly how it was closed, its collateral lien (if any) is
     * released, and it's marked paid.
     *
     * Journal (source 'loan_topup_disbursement'):
     *   Dr Loan Portfolio      new principal (full)
     *   Cr Loan Portfolio      old loan's outstanding principal (removed from the books)
     *   Cr Interest Income     old loan's outstanding interest (recognized as earned via the roll-over)
     *   Cr Cash                whatever's left over, actually handed to the client
     *
     * Only principal + interest are netted — any outstanding penalties or
     * processing fee on the old loan are NOT auto-settled here and should
     * still be collected/written off separately.
     */
    private function disburseTopUp(Loan $loan, int $managerId)
    {
        $oldLoan = Loan::with('client')->lockForUpdate()->find($loan->replaces_loan_id);

        if (!$oldLoan || $oldLoan->loan_manager_id !== $managerId) {
            return back()->with('error', 'The loan this was supposed to top up no longer exists.');
        }

        if (in_array($oldLoan->status, ['paid', 'written_off'], true)) {
            return back()->with('error', 'The old loan (#' . $oldLoan->id . ') has already been closed — this top-up can no longer be disbursed as a top-up. Reverse it back to "approved" and re-check, or disburse it as an ordinary loan instead.');
        }

        $principalPaid = (float) $oldLoan->payments()->sum('principal_paid');
        $interestPaid = (float) $oldLoan->payments()->sum('interest_paid');

        $principalOutstanding = max(0, round($oldLoan->principal_amount - $principalPaid, 2));
        $interestOutstanding = max(0, round($oldLoan->totalInterestDue() - $interestPaid, 2));
        $settlement = round($principalOutstanding + $interestOutstanding, 2);
        $netCashOut = round($loan->principal_amount - $settlement, 2);

        if ($netCashOut < -0.01) {
            return back()->with('error', 'The old loan\'s outstanding balance (' . number_format($settlement) . ') is now more than this top-up\'s principal (' . number_format($loan->principal_amount) . ') — someone likely made a payment on the old loan since this was applied for. Reject this application and start a new top-up for the current balance.');
        }

        DB::transaction(function () use ($loan, $oldLoan, $managerId, $principalOutstanding, $interestOutstanding, $settlement, $netCashOut) {
            $entry = $this->postTopUpDisbursementJournalEntry($loan, $oldLoan, $managerId, $principalOutstanding, $interestOutstanding, $netCashOut);

            if ($settlement > 0.01) {
                $settlementPayment = Payment::create([
                    'loan_id' => $oldLoan->id,
                    'payment_date' => now()->toDateString(),
                    'amount_paid' => $settlement,
                    'principal_paid' => $principalOutstanding,
                    'interest_paid' => $interestOutstanding,
                    'payment_method' => 'Loan Top-Up',
                    'receipt_number' => 'TOPUP-' . $loan->id,
                    'notes' => 'Settled via top-up into loan #' . $loan->id . ' (Ref: ' . $loan->reference_id . ')',
                    'journal_entry_id' => $entry?->id,
                ]);
            }

            if ($oldLoan->collateral_locked > 0) {
                $savingsAccount = MfiAccount::where('loan_manager_id', $managerId)
                    ->where('client_id', $oldLoan->client_id)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($savingsAccount) {
                    $release = min($oldLoan->collateral_locked, $savingsAccount->lien_amount);
                    $savingsAccount->decrement('lien_amount', $release);
                }

                $oldLoan->collateral_locked = 0;
            }

            $oldLoan->status = 'paid';
            $oldLoan->save();

            $loan->update([
                'approval_status' => 'disbursed',
                'status' => 'active',
                'disbursement_journal_entry_id' => $entry?->id,
            ]);

            RepaymentScheduleGenerator::generate($loan);
        });

        NotificationService::notify(
            $managerId,
            'loan_disbursed',
            'Loan #' . $loan->id . ' disbursed (top-up)',
            ($loan->client?->name ?? 'Client') . '\'s loan #' . $oldLoan->id . ' was topped up into new loan #' . $loan->id . '. ' .
                ($netCashOut > 0.01 ? number_format($netCashOut) . ' paid out in cash.' : 'No cash paid out — fully absorbed by the old balance.'),
            $loan->client_id,
            route('loans.show', $loan->id),
            'Your loan #' . $oldLoan->id . ' has been topped up into new loan #' . $loan->id . ' (' . \App\Models\LoanManager::getCurrency() . ' ' . number_format($loan->principal_amount) . '). ' .
                ($netCashOut > 0.01 ? \App\Models\LoanManager::getCurrency() . ' ' . number_format($netCashOut) . ' has been paid out to you.' : 'The old balance fully covered the new amount, so no cash was paid out.')
        );

        return redirect()->route('loans.show', $loan->id)->with(
            'success',
            'Top-up disbursed. Loan #' . $oldLoan->id . ' settled (' . number_format($settlement) . ') and closed. ' .
                ($netCashOut > 0.01 ? number_format($netCashOut) . ' paid out to the client.' : 'Nothing left to pay out — the new principal only covered the old balance.')
        );
    }

    private function postTopUpDisbursementJournalEntry(Loan $newLoan, Loan $oldLoan, int $managerId, float $principalOutstanding, float $interestOutstanding, float $netCashOut): ?JournalEntry
    {
        $loanPortfolio = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '1100')->where('is_active', true)->first();
        $cash = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '1000')->where('is_active', true)->first();
        $interestIncome = ChartOfAccount::where('loan_manager_id', $managerId)->where('code', '4000')->where('is_active', true)->first();

        if (!$loanPortfolio || !$cash) {
            return null;
        }

        $entry = JournalEntry::create([
            'loan_manager_id' => $managerId,
            'entry_date' => now()->toDateString(),
            'reference_no' => $newLoan->reference_id,
            'narration' => 'Loan top-up — loan #' . $oldLoan->id . ' rolled into #' . $newLoan->id . ' (' . ($newLoan->client->name ?? 'client #' . $newLoan->client_id) . ')',
            'created_by' => Auth::id(),
            'source' => 'loan_topup_disbursement',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $loanPortfolio->id,
            'debit' => $newLoan->principal_amount,
            'credit' => 0,
            'description' => 'New principal disbursed (loan #' . $newLoan->id . ')',
        ]);

        // Track credits as they're posted so the entry always balances
        // exactly against the debit above, even if the tenant hasn't set
        // up a 4000 Interest Income account — in that case the interest
        // portion simply falls through into the cash line below rather
        // than being silently dropped (which would leave this entry
        // permanently out of balance).
        $creditedSoFar = 0.0;

        if ($principalOutstanding > 0.01) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $loanPortfolio->id,
                'debit' => 0,
                'credit' => $principalOutstanding,
                'description' => 'Old loan #' . $oldLoan->id . ' principal settled via top-up',
            ]);
            $creditedSoFar += $principalOutstanding;
        }

        if ($interestOutstanding > 0.01 && $interestIncome) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $interestIncome->id,
                'debit' => 0,
                'credit' => $interestOutstanding,
                'description' => 'Old loan #' . $oldLoan->id . ' interest settled via top-up',
            ]);
            $creditedSoFar += $interestOutstanding;
        }

        $cashCredit = round($newLoan->principal_amount - $creditedSoFar, 2);

        if ($cashCredit > 0.01) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $cash->id,
                'debit' => 0,
                'credit' => $cashCredit,
                'description' => $cashCredit > $netCashOut + 0.01
                    ? 'Net cash out to client (includes old interest — no Interest Income account configured)'
                    : 'Net cash out to client',
            ]);
        }

        return $entry;
    }

    /**
     * Show loan repayment calculator.
     */
    public function showCalculator(Request $request)
    {
        $schedule = [];
        $calculationPerformed = false;
        $totalInterest = 0; 
        $totalRepayable = 0;
        
        $principal = $request->input('principal_amount', 1000000);
        $interestRate = $request->input('interest_rate', 10);
        $interestMethod = $request->input('interest_method', 'flat');
        $term = $request->input('term', 12);
        $frequency = $request->input('repayment_frequency', 'Monthly');

        if ($request->has('calculate')) {
            $calculationPerformed = true;

            if ($principal > 0 && $term > 0) {
                // A throwaway, unsaved Loan so the calculator shares exactly
                // the same math as real loans (totalInterestDue()/
                // amortizationSchedule()) instead of a third copy of it.
                $previewLoan = new Loan([
                    'principal_amount' => $principal,
                    'interest_rate' => $interestRate,
                    'interest_method' => $interestMethod,
                    'term' => $term,
                ]);

                $totalInterest = $previewLoan->totalInterestDue();
                $totalRepayable = $previewLoan->principalInterestDue();

                $startDate = Carbon::today();

                if ($interestMethod === 'reducing_balance') {
                    $balance = $totalRepayable;

                    foreach ($previewLoan->amortizationSchedule() as $period) {
                        $dueDate = $startDate->copy();
                        switch ($frequency) {
                            case 'Daily':  $dueDate->addDays($period['installment']); break;
                            case 'Weekly': $dueDate->addWeeks($period['installment']); break;
                            default: $dueDate->addMonths($period['installment']); break;
                        }

                        $schedule[] = [
                            'period' => $period['installment'],
                            'due_date' => $dueDate->toDateString(),
                            'payment_amount' => $period['principal'] + $period['interest'],
                            'principal' => $period['principal'],
                            'interest' => $period['interest'],
                            'balance' => $period['balance'],
                        ];
                    }
                } else {
                    $paymentPerPeriod = $totalRepayable / $term;
                    $principalComponent = $principal / $term;
                    $interestComponent = $totalInterest / $term;

                    $balance = $totalRepayable;

                    for ($i = 1; $i <= $term; $i++) {
                        $balance -= $paymentPerPeriod;
                        $dueDate = $startDate->copy();

                        switch ($frequency) {
                            case 'Daily':  $dueDate->addDays($i); break;
                            case 'Weekly': $dueDate->addWeeks($i); break;
                            default: $dueDate->addMonths($i); break;
                        }

                        $schedule[] = [
                            'period' => $i,
                            'due_date' => $dueDate->toDateString(),
                            'payment_amount' => $paymentPerPeriod,
                            'principal' => $principalComponent,
                            'interest' => $interestComponent,
                            'balance' => ($i == $term) ? 0 : $balance
                        ];
                    }
                }
            }
        }

        return view('loan-manager.loans.calculator', compact(
            'schedule', 'principal', 'interestRate', 'interestMethod', 'term', 'frequency',
            'calculationPerformed', 'totalRepayable', 'totalInterest'
        ));
    }

    /**
     * Show loan details.
     */
    public function show(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }
        
        $loan->load('payments', 'guarantors', 'collaterals', 'client', 'clientGroup', 'reschedules.rescheduledBy', 'penalties.appliedBy', 'penalties.removedBy');

        $penaltySettings = \App\Models\LoanPenaltySetting::where('loan_manager_id', $loan->loan_manager_id)->first();
        $defaultPenaltyAmount = optional($penaltySettings)->penalty_type === 'flat'
            ? optional($penaltySettings)->penalty_amount
            : round($loan->balance() * (optional($penaltySettings)->penalty_percent ?? 0) / 100, 2);

        $principal = $loan->principal_amount;
        $totalInterest = $principal * ($loan->interest_rate / 100);
        $totalRepayable = $principal + $totalInterest;
        $term = $loan->term > 0 ? $loan->term : 1;
        $paymentPerPeriod = $totalRepayable / $term;
        $principalComponent = $principal / $term;
        $interestComponent = $totalInterest / $term;
        $schedule = [];
        $balance = $totalRepayable;
        $startDate = Carbon::parse($loan->start_date);

        for ($i = 1; $i <= $term; $i++) {
            $balance -= $paymentPerPeriod;
            $dueDate = $startDate->copy();
            switch ($loan->repayment_frequency) {
                case 'Daily':  $dueDate->addDays($i); break;
                case 'Weekly': $dueDate->addWeeks($i); break;
                default: $dueDate->addMonths($i); break;
            }
            $schedule[] = [ 'period' => $i, 'due_date' => $dueDate->toDateString(), 'payment_amount' => $paymentPerPeriod, 'principal' => $principalComponent, 'interest' => $interestComponent, 'balance' => ($i == $term) ? 0 : $balance ];
        }
        
        return view('loan-manager.loans.show', compact('loan', 'schedule', 'defaultPenaltyAmount'));
    }

    /**
     * Show edit loan form. Only for applications still awaiting approval —
     * see update() for why editing stops there.
     */
    public function edit(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'pending') {
            return redirect()->route('loans.show', $loan->id)
                ->with('error', 'Only a pending application can be edited directly. Use Reschedule, Write Off, or Reverse Disbursement instead.');
        }

        return view('loan-manager.loans.edit', compact('loan'));
    }

    /**
     * Update loan record. Deliberately restricted to applications that
     * haven't been approved yet — once approved/disbursed, a loan has (or
     * is about to have) journal entries, a repayment schedule, and
     * possibly payments built on top of its current terms. Free-editing
     * principal/rate/term at that point would silently desync all of that
     * from what was actually disbursed/posted; reschedule() exists
     * specifically to change terms on a live loan with a proper audit
     * trail instead. Status is no longer editable here at all — it must
     * only ever change through the approve/disburse/writeOff/reverse
     * actions, never a free dropdown, so every other report that reads
     * status/approval_status together can trust they're always in sync.
     */
    public function update(Request $request, Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status !== 'pending') {
            return redirect()->route('loans.show', $loan->id)
                ->with('error', 'Only a pending application can be edited directly. Use Reschedule, Write Off, or Reverse Disbursement instead.');
        }

        $validatedData = $request->validate([
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'repayment_frequency' => 'required|string|in:Daily,Weekly,Monthly',
            'start_date' => 'required|date',
        ]);
        $loan->update($validatedData);
        return redirect()->route('loans.show', $loan->id)->with('status', 'Loan application details have been updated successfully!');
    }

    /**
     * Delete loan record.
     */
    /**
     * Hard-delete a loan record. Only permitted for applications that never
     * disbursed and have no payment history — a real, disbursed loan is
     * financial history and must be unwound via write-off/reversal instead
     * (writeOff(), reverseDisbursement()), never deleted outright.
     */
    public function destroy(Loan $loan)
    {
        if (Auth::user()->loanManager->id !== $loan->loan_manager_id) { abort(403); }

        if ($loan->approval_status === 'disbursed' || $loan->status === 'active' || $loan->status === 'paid') {
            return back()->with('error', 'This loan has been disbursed and cannot be deleted — use Write Off or Reverse Disbursement instead to keep an audit trail.');
        }

        if ($loan->payments()->exists()) {
            return back()->with('error', 'This loan has payment history and cannot be deleted.');
        }

        if ($loan->collateral_locked > 0) {
            $this->releaseLoanCollateral($loan);
        }

        $loan->delete();
        return redirect()->route('loans.index')->with('status', 'Loan application deleted.');
    }

    /**
     * Generate printable Loan Agreement.
     * Matches Route name 'loans.downloadAgreement'
     */
    public function downloadLoanAgreement($id)
    {
        $loan = Loan::with(['client', 'loanManager', 'guarantors', 'collaterals'])->findOrFail($id);
        
        // Security check
        if ($loan->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized');
        }

        // Return the printable layout view
        return view('loan-manager.loans.agreement-pdf', compact('loan'));
    }
}