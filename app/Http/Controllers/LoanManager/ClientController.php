<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    // --- NEW: Global Check for Client Eligibility ---
    public function checkGlobal(Request $request)
    {
        $nationalId = $request->input('national_id');
        $phoneNumber = $request->input('phone_number');

        if (!$nationalId && !$phoneNumber) {
            return response()->json(['status' => 'error', 'message' => 'Provide ID or Phone']);
        }

        // Search the ENTIRE system (not just this manager's clients)
        $clients = Client::with('loans.loanManager')
            ->where(function($q) use ($nationalId, $phoneNumber) {
                if ($nationalId) $q->where('national_id', $nationalId);
                if ($phoneNumber) $q->orWhere('phone_number', $phoneNumber);
            })
            ->get();

        if ($clients->isEmpty()) {
            return response()->json(['status' => 'clean', 'message' => 'No existing records found globally.']);
        }

        $report = [];
        $hasActiveLoans = false;

        foreach ($clients as $client) {
            // Filter for active loans
            $activeLoans = $client->loans->filter(function($loan) {
                return !in_array($loan->status, ['paid', 'rejected', 'closed']);
            });

            if ($activeLoans->count() > 0) {
                $hasActiveLoans = true;
                foreach ($activeLoans as $loan) {
                    $managerName = $loan->loanManager->business_name ?? $loan->loanManager->user->name;
                    $report[] = [
                        'manager' => $managerName,
                        'amount' => number_format($loan->principal_amount),
                        'status' => ucfirst($loan->status)
                    ];
                }
            }
        }

        if ($hasActiveLoans) {
            return response()->json([
                'status' => 'warning', 
                'message' => 'Client has pending loans with other managers!',
                'details' => $report
            ]);
        }

        return response()->json(['status' => 'info', 'message' => 'Client exists in system but has NO active loans.']);
    }

    // --- STANDARD METHODS ---

    public function index(Request $request)
    {
        $manager = Auth::user()->loanManager;
        $query = $manager->clients();

        $search = $request->input('search') ?? $request->input('q') ?? $request->input('term');

        if ($search) {
            $searchTerm = strtolower(trim($search));
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhere('national_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($filter = $request->input('filter')) {
            switch ($filter) {
                case 'not_paid':
                    $query->whereHas('loans', function($q) { $q->where('status', 'active'); });
                    break;
                case 'with_loans':
                    $query->has('loans');
                    break;
                case 'no_loans':
                    $query->doesntHave('loans');
                    break;
            }
        }

        $clients = $query->with('assignedUser')->latest()->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($clients);
        }

        return view('loan-manager.clients.index', compact('clients'));
    }

    public function create()
    {
        $managerId = Auth::user()->loanManager->id;
        $staffMembers = \App\Models\User::where('loan_manager_id', $managerId)->where('role', 'cashier')->orderBy('name')->get();
        return view('loan-manager.clients.create', compact('staffMembers'));
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => ['nullable', 'string', 'max:20', Rule::unique('clients')->where('loan_manager_id', $managerId)],
            'phone_number' => ['required', 'string', 'max:20', Rule::unique('clients')->where('loan_manager_id', $managerId)],
            'address' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'business_occupation' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:Male,Female,Other',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'client_type' => 'nullable|string|in:individual,business',
            'business_name' => 'nullable|required_if:client_type,business|string|max:255',
            'business_registration_number' => 'nullable|string|max:100',
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('loan_manager_id', $managerId)],
            'preferred_notification_channel' => 'nullable|string|in:sms,email,none',
        ]);

        $validated['loan_manager_id'] = $managerId;

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $this->storeClientFile($request->file('photo'), 'photos');
        }
        if ($request->hasFile('id_document')) {
            $validated['id_document_path'] = $this->storeClientFile($request->file('id_document'), 'ids');
        }
        unset($validated['photo'], $validated['id_document']);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $this->authorizeManager($client);
        $client->load(['loans.payments', 'groups', 'assignedUser']);
        $groups = \App\Models\ClientGroup::where('loan_manager_id', $client->loan_manager_id)->orderBy('name')->get();
        return view('loan-manager.clients.show', compact('client', 'groups'));
    }

    public function edit(Client $client)
    {
        $this->authorizeManager($client);
        $staffMembers = \App\Models\User::where('loan_manager_id', $client->loan_manager_id)->where('role', 'cashier')->orderBy('name')->get();
        return view('loan-manager.clients.edit', compact('client', 'staffMembers'));
    }

    public function update(Request $request, Client $client)
    {
        $this->authorizeManager($client);
        $managerId = $client->loan_manager_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => ['nullable', 'string', 'max:20', Rule::unique('clients')->ignore($client->id)->where('loan_manager_id', $managerId)],
            'phone_number' => ['required', 'string', 'max:20', Rule::unique('clients')->ignore($client->id)->where('loan_manager_id', $managerId)],
            'address' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'business_occupation' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:Male,Female,Other',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'client_type' => 'nullable|string|in:individual,business',
            'business_name' => 'nullable|required_if:client_type,business|string|max:255',
            'business_registration_number' => 'nullable|string|max:100',
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('loan_manager_id', $managerId)],
            'preferred_notification_channel' => 'nullable|string|in:sms,email,none',
        ]);

        if ($request->hasFile('photo')) {
            $this->deleteClientFile($client->photo_path);
            $validated['photo_path'] = $this->storeClientFile($request->file('photo'), 'photos');
        }
        if ($request->hasFile('id_document')) {
            $this->deleteClientFile($client->id_document_path);
            $validated['id_document_path'] = $this->storeClientFile($request->file('id_document'), 'ids');
        }
        unset($validated['photo'], $validated['id_document']);

        $client->update($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Client details updated successfully!');
    }

    /**
     * Remove a client. Client uses SoftDeletes, so this never erases the
     * row outright — but a soft-deleted client vanishes from the client
     * list and every dropdown app-wide, which is just as dangerous as a
     * hard delete if they still have loan or savings history: it would
     * look like the debt/deposit and the person it belongs to both
     * disappeared. Only allowed for a client with no real financial
     * footprint; anyone else should be blacklisted instead, which blocks
     * new business while keeping their record and history intact.
     */
    public function destroy(Client $client)
    {
        $this->authorizeManager($client);

        $hasLoanHistory = $client->loans()->where('status', '!=', 'rejected')->exists();
        if ($hasLoanHistory) {
            return back()->with('error', 'This client has loan history and cannot be deleted — blacklist them instead to block new loans while keeping their record.');
        }

        $hasPaymentHistory = \App\Models\Payment::whereHas('loan', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })->exists();
        if ($hasPaymentHistory) {
            return back()->with('error', 'This client has payment history and cannot be deleted.');
        }

        $hasAccountHistory = $client->mfiAccounts()
            ->where(function ($q) {
                $q->where('status', '!=', 'closed')->orWhere('balance', '>', 0)->orWhere('units', '>', 0);
            })
            ->exists();
        if ($hasAccountHistory) {
            return back()->with('error', 'This client has an open savings, shares, or fixed deposit account and cannot be deleted — close those accounts first.');
        }

        $hasTransactionHistory = \App\Models\MfiTransaction::where('client_id', $client->id)->exists();
        if ($hasTransactionHistory) {
            return back()->with('error', 'This client has transaction history and cannot be deleted — blacklist them instead.');
        }

        $this->deleteClientFile($client->photo_path);
        $this->deleteClientFile($client->id_document_path);
        $client->groups()->detach();
        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Client has been deleted successfully!');
    }

    /**
     * Blacklist a client — blocks new loan applications (see the check in
     * LoanController::store()) without touching anything about their
     * existing loans/savings, and without deleting them.
     */
    public function blacklist(Request $request, Client $client)
    {
        $this->authorizeManager($client);

        $validated = $request->validate([
            'blacklist_reason' => 'nullable|string|max:1000',
        ]);

        $client->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $validated['blacklist_reason'] ?? null,
            'blacklisted_at' => now(),
            'blacklisted_by' => Auth::id(),
        ]);

        return back()->with('success', $client->name . ' has been blacklisted.');
    }

    public function unblacklist(Client $client)
    {
        $this->authorizeManager($client);

        $client->update([
            'is_blacklisted' => false,
            'blacklist_reason' => null,
            'blacklisted_at' => null,
            'blacklisted_by' => null,
        ]);

        return back()->with('success', $client->name . ' has been removed from the blacklist.');
    }

    /**
     * Move a client to a different Client Group — a convenience over
     * manually editing membership on both groups. Detaches the client from
     * every group they currently belong to and attaches them to the chosen
     * one (or just removes them from their current group if "no group" is
     * selected). Existing loans keep whatever client_group_id they were
     * disbursed under — this only affects group membership going forward.
     */
    public function transferGroup(Request $request, Client $client)
    {
        $this->authorizeManager($client);
        $managerId = $client->loan_manager_id;

        $validated = $request->validate([
            'client_group_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('client_groups', 'id')->where('loan_manager_id', $managerId),
            ],
        ]);

        $client->groups()->sync(!empty($validated['client_group_id']) ? [$validated['client_group_id']] : []);

        $groupName = !empty($validated['client_group_id'])
            ? \App\Models\ClientGroup::find($validated['client_group_id'])->name
            : null;

        return back()->with('success', $groupName
            ? $client->name . ' moved to ' . $groupName . '.'
            : $client->name . ' removed from their group.');
    }

    /**
     * Quick individual ↔ business conversion — a lighter-weight alternative
     * to the full Edit Client form for just flipping the account type.
     * Nothing else about the client record is touched.
     */
    public function convertType(Request $request, Client $client)
    {
        $this->authorizeManager($client);

        $validated = $request->validate([
            'client_type' => 'required|string|in:individual,business',
            'business_name' => 'nullable|required_if:client_type,business|string|max:255',
            'business_registration_number' => 'nullable|string|max:100',
        ]);

        $client->update([
            'client_type' => $validated['client_type'],
            'business_name' => $validated['client_type'] === 'business' ? ($validated['business_name'] ?? null) : $client->business_name,
            'business_registration_number' => $validated['client_type'] === 'business'
                ? ($validated['business_registration_number'] ?? null)
                : $client->business_registration_number,
        ]);

        return back()->with('success', $client->name . ' converted to ' . ucfirst($validated['client_type']) . ' account.');
    }

    /**
     * Printable client statement: loan activity and (for MFI tenants)
     * savings activity, shown as separate account summaries since they're
     * not the same balance (one is owed BY the client, the other TO them).
     */
    public function statement(Client $client)
    {
        $this->authorizeManager($client);

        $loanTransactions = collect();
        foreach ($client->loans()->with('payments')->get() as $loan) {
            $interest = $loan->principal_amount * ($loan->interest_rate / 100);
            $loanTransactions->push((object)[
                'date' => $loan->start_date,
                'description' => "Loan Disbursed (Ref: {$loan->reference_id})",
                'debit' => $loan->principal_amount + $interest,
                'credit' => 0,
            ]);
            foreach ($loan->payments as $payment) {
                $loanTransactions->push((object)[
                    'date' => $payment->payment_date,
                    'description' => 'Payment Received (Receipt: ' . ($payment->receipt_number ?? $payment->id) . ')',
                    'debit' => 0,
                    'credit' => $payment->amount_paid,
                ]);
            }
        }
        $loanTransactions = $loanTransactions->sortBy('date')->values();

        $isMfi = (bool) (Auth::user()->loanManager->is_mfi ?? false);
        $savingsAccount = null;

        if ($isMfi) {
            $savingsAccount = \App\Models\MfiAccount::where('loan_manager_id', $client->loan_manager_id)
                ->where('client_id', $client->id)
                ->where('account_type', 'savings')
                ->with(['transactions' => fn($q) => $q->oldest('transaction_date')->oldest('id')])
                ->first();
        }

        return view('loan-manager.clients.statement-pdf', [
            'client' => $client,
            'loanTransactions' => $loanTransactions,
            'savingsAccount' => $savingsAccount,
        ]);
    }

    public function showLedger(Client $client)
    {
        $this->authorizeManager($client);

        $transactions = collect();
        foreach ($client->loans()->with('payments')->get() as $loan) {
            $interest = $loan->principal_amount * ($loan->interest_rate / 100);
            $transactions->push((object)[
                'date' => $loan->start_date,
                'description' => "Loan Disbursed (ID: {$loan->id})",
                'debit' => $loan->principal_amount + $interest,
                'credit' => 0
            ]);
            foreach ($loan->payments as $payment) {
                $transactions->push((object)[
                    'date' => $payment->payment_date,
                    'description' => "Payment Received (Receipt: {$payment->id})",
                    'debit' => 0,
                    'credit' => $payment->amount_paid
                ]);
            }
        }

        return view('loan-manager.clients.ledger', [
            'client' => $client,
            'transactions' => $transactions->sortBy('date')
        ]);
    }

    private function authorizeManager(Client $client)
    {
        if (Auth::user()->loanManager->id !== $client->loan_manager_id) {
            abort(403, 'Unauthorized: This client does not belong to you.');
        }
    }

    /**
     * Move an uploaded client file (photo or ID document) into private
     * storage and return its relative path. Deliberately stored OUTSIDE
     * public/ — under storage/app/, which the web server never serves
     * directly — so these files (often scanned national IDs) can only be
     * retrieved through the authenticated photo()/idDocument() routes
     * below, which check the requesting manager actually owns the client
     * before streaming anything back.
     */
    private function storeClientFile($file, string $folder): string
    {
        $filename = uniqid($folder . '_') . '.' . $file->getClientOriginalExtension();
        $destination = storage_path('app/client_documents/' . $folder);

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return $folder . '/' . $filename;
    }

    /**
     * Delete a previously-stored client file (e.g. when it's being
     * replaced). Safe to call with null/blank/missing paths.
     */
    private function deleteClientFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = storage_path('app/client_documents/' . $relativePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    /**
     * Stream a client's photo back to the browser. Only reachable by the
     * manager who owns the client (authorizeManager aborts 403 otherwise) —
     * this is the whole point of keeping these files out of public/.
     */
    public function photo(Client $client)
    {
        return $this->serveClientFile($client, $client->photo_path);
    }

    /**
     * Stream a client's ID document back to the browser, same ownership
     * check as photo().
     */
    public function idDocument(Client $client)
    {
        return $this->serveClientFile($client, $client->id_document_path);
    }

    private function serveClientFile(Client $client, ?string $relativePath)
    {
        $this->authorizeManager($client);

        if (!$relativePath) {
            abort(404);
        }

        $fullPath = storage_path('app/client_documents/' . $relativePath);

        if (!is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}