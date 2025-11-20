<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // NOTE: If Auth::user()->loanManager is a separate model, ensure the relationship 
        // back to the User model is stable. If LoanManager IS the User, simplify to Auth::user()->clients().
        $query = Auth::user()->loanManager->clients();

        if ($search = $request->input('search')) {
            $searchTerm = strtolower($search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $clients = $query->latest()->get();

        return view('loan-manager.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('loan-manager.clients.create');
    }

    public function store(Request $request)
    {
        // === FIX FOR ERROR 2 (Foreign Key Constraint Violation) ===
        // The error log indicates 'clients.loan_manager_id' must reference 'users.id'.
        // If your database requires this, we MUST set the loan_manager_id to the User's ID.
        $userId = Auth::id(); // Get the ID of the authenticated User

        // Use the authenticated User's ID for uniqueness scope check, if required
        // Note: I will use the LoanManager model's ID if that is the FK you intended, 
        // but given the error logs, I'll use Auth::id() (the User ID) for safety.
        // I'll keep the uniqueness logic tied to the User ID, assuming LoanManager is a profile extension of User.
        $managerId = Auth::user()->loanManager->id;

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            
            'national_id' => [
                'nullable',
                'string',
                'max:20',
                // Check uniqueness only within the clients managed by THIS user
                Rule::unique('clients', 'national_id')->where('loan_manager_id', $managerId),
            ],
            
            'phone_number' => [
                'required',
                'string',
                'max:20',
                // Check uniqueness only within the clients managed by THIS user
                Rule::unique('clients', 'phone_number')->where('loan_manager_id', $managerId),
            ],
            'address' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'occupation' => 'nullable|string|max:255',
        ]);
        
        // Inject the loan_manager_id before creation, using the authenticated user's ID
        // to satisfy the foreign key constraint to the 'users' table.
        $validatedData['loan_manager_id'] = $userId;
        
        // Use the Client model to create the record, ensuring the correct foreign key is set.
        Client::create($validatedData);

        return redirect()->route('clients.index')->with('success', 'New client added successfully!');
    }

    /**
     * Display the specified client's profile details.
     * * @param \App\Models\Client $client
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    // === FIX FOR ERROR 1 (Undefined method) ===
    public function show(Client $client)
    {
        // Security check: ensure the client belongs to the logged-in manager
        if (Auth::user()->loanManager->id !== $client->loan_manager_id) {
            abort(403, 'Unauthorized action.');
        }

        // Load necessary relationships, e.g., loans and payments
        $client->load(['loans.payments']);
        
        return view('loan-manager.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        if (Auth::user()->loanManager->id !== $client->loan_manager_id) {
            abort(403);
        }
        return view('loan-manager.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        if (Auth::user()->loanManager->id !== $client->loan_manager_id) {
            abort(403);
        }

        $managerId = $client->loan_manager_id;

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            
            'national_id' => [
                'nullable',
                'string',
                'max:20',
                // Ignore the current client ID ($client->id) while checking uniqueness under this manager
                Rule::unique('clients', 'national_id')
                    ->ignore($client->id)
                    ->where('loan_manager_id', $managerId),
            ],
            
            'phone_number' => [
                'required',
                'string',
                'max:20',
                // Ignore the current client ID ($client->id) while checking uniqueness under this manager
                Rule::unique('clients', 'phone_number')
                    ->ignore($client->id)
                    ->where('loan_manager_id', $managerId),
            ],
            'address' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'occupation' => 'nullable|string|max:255',
        ]);

        $client->update($validatedData);

        return redirect()->route('clients.show', $client)->with('success', 'Client details updated successfully!');
    }

    public function destroy(Client $client)
    {
        if (Auth::user()->loanManager->id !== $client->loan_manager_id) {
            abort(403);
        }
        $client->delete();
        return redirect()->route('clients.index')->with('status', 'Client has been deleted successfully!');
    }

    /**
     * Display a printable ledger for a specific client.
     */
    public function showLedger(Client $client)
    {
        // Security check to ensure the client belongs to the logged-in manager
        if ($client->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized action.');
        }

        $transactions = collect();

        // Loop through each loan for the client
        foreach ($client->loans()->with('payments')->get() as $loan) {
            // Add the loan disbursement as a debit
            // NOTE: Assumes $loan->total_interest is calculated via a helper or mutator
            $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
            $transactions->push((object)[
                'date' => $loan->start_date,
                'description' => "Loan Disbursed (ID: {$loan->id})",
                'debit' => $loan->principal_amount + $totalInterest, // Include interest in the total debit
                'credit' => 0,
            ]);

            // Add all payments for that loan as credits
            foreach ($loan->payments as $payment) {
                $transactions->push((object)[
                    'date' => $payment->payment_date,
                    'description' => "Payment Received (Receipt: {$payment->id})",
                    'debit' => 0,
                    'credit' => $payment->amount_paid,
                ]);
            }
        }

        // Return the view with the client and sorted transactions
        return view('loan-manager.clients.ledger', [
            'client' => $client,
            'transactions' => $transactions->sortBy('date'),
        ]);
    }
}