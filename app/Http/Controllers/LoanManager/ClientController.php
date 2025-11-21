<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller; // Reverted to standard Controller
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    // -------------------------------------------------------------------------
    // NOTE: We removed the __construct() to stop the crash.
    // We have moved the 'elevated' middleware check to your web.php file instead.
    // This guarantees security without the "Undefined method" error.
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $manager = Auth::user()->loanManager;
        
        // Start with clients belonging to this manager
        $query = $manager->clients();

        // --- SEARCH / TRACING LOGIC ---
        // We check 'search', 'q', and 'term' to ensure the search bar works no matter what it sends
        $search = $request->input('search') ?? $request->input('q') ?? $request->input('term');

        if ($search) {
            $searchTerm = strtolower(trim($search));
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhere('national_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%"); // Added Email for better tracing
            });
        }

        // --- FILTER LOGIC ---
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

        $clients = $query->latest()->get();

        // Support for AJAX search (dropdowns)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($clients);
        }

        return view('loan-manager.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('loan-manager.clients.create');
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
            'occupation' => 'nullable|string|max:255',
        ]);
        
        $validated['loan_manager_id'] = $managerId;
        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $this->authorizeManager($client);
        $client->load(['loans.payments']);
        return view('loan-manager.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $this->authorizeManager($client);
        return view('loan-manager.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        // Security is now handled in web.php (middleware: elevated)
        $this->authorizeManager($client);
        
        $managerId = $client->loan_manager_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => ['nullable', 'string', 'max:20', Rule::unique('clients')->ignore($client->id)->where('loan_manager_id', $managerId)],
            'phone_number' => ['required', 'string', 'max:20', Rule::unique('clients')->ignore($client->id)->where('loan_manager_id', $managerId)],
            'address' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'occupation' => 'nullable|string|max:255',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Client details updated successfully!');
    }

    public function destroy(Client $client)
    {
        // Security is now handled in web.php (middleware: elevated)
        $this->authorizeManager($client);
        $client->delete();
        return redirect()->route('clients.index')->with('status', 'Client has been deleted successfully!');
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
}