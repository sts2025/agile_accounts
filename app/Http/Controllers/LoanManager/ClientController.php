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
        $managerId = Auth::user()->loanManager->id;

        // --- NEW VALIDATION: Enforce uniqueness and required fields ---
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            
            // Assuming national_id exists in your database structure for security
            'national_id' => [
                'nullable', // Changed to nullable based on typical forms, change to 'required' if mandatory
                'string',
                'max:20',
                // Unique check: Must be unique among clients tied to THIS manager
                Rule::unique('clients', 'national_id')->where('loan_manager_id', $managerId),
            ],
            
            'phone_number' => [
                'required',
                'string',
                'max:20',
                // Unique check: Must be unique among clients tied to THIS manager
                Rule::unique('clients', 'phone_number')->where('loan_manager_id', $managerId),
            ],
            'address' => 'required|string|max:255', // Changed to required based on your loan validation needs
            'email' => 'nullable|email|max:255', // Added common fields
            'date_of_birth' => 'nullable|date|before:today', // Added common fields
            'occupation' => 'nullable|string|max:255', // Used 'occupation' based on standard schema
        ]);

        Auth::user()->loanManager->clients()->create($validatedData);

        return redirect()->route('clients.index')->with('success', 'New client added successfully!');
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

        // --- NEW VALIDATION: Enforce uniqueness but ignore the current client's ID ---
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