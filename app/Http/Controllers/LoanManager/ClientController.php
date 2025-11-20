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
        // Start the query for clients belonging to the logged-in Loan Manager
        $query = Auth::user()->loanManager->clients();

        // 1. Handle Search
        if ($search = $request->input('search')) {
            $searchTerm = strtolower($search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        // 2. Handle Sidebar Filters
        if ($filter = $request->input('filter')) {
            switch ($filter) {
                case 'not_paid':
                    // Clients with currently ACTIVE loans (owing money)
                    // Assumes your loans table has a 'status' column with value 'active'
                    $query->whereHas('loans', function($q) {
                        $q->where('status', 'active');
                    });
                    break;

                case 'with_loans':
                    // Clients who have ANY history of loans (active or paid)
                    $query->has('loans');
                    break;

                case 'no_loans':
                    // Clients who have NEVER taken a loan
                    $query->doesntHave('loans');
                    break;
            }
        }

        // Get results (sorting by latest created first)
        $clients = $query->latest()->get();

        return view('loan-manager.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('loan-manager.clients.create');
    }

    public function store(Request $request)
    {
        $userId = Auth::id(); 
        $managerId = Auth::user()->loanManager->id;

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => [
                'nullable', 'string', 'max:20',
                Rule::unique('clients', 'national_id')->where('loan_manager_id', $managerId),
            ],
            'phone_number' => [
                'required', 'string', 'max:20',
                Rule::unique('clients', 'phone_number')->where('loan_manager_id', $managerId),
            ],
            'address' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'occupation' => 'nullable|string|max:255',
        ]);
        
        $validatedData['loan_manager_id'] = $userId;
        
        Client::create($validatedData);

        return redirect()->route('clients.index')->with('success', 'New client added successfully!');
    }

    public function show(Client $client)
    {
        if (Auth::user()->loanManager->id !== $client->loan_manager_id) {
            abort(403, 'Unauthorized action.');
        }

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
                'nullable', 'string', 'max:20',
                Rule::unique('clients', 'national_id')->ignore($client->id)->where('loan_manager_id', $managerId),
            ],
            'phone_number' => [
                'required', 'string', 'max:20',
                Rule::unique('clients', 'phone_number')->ignore($client->id)->where('loan_manager_id', $managerId),
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

    public function showLedger(Client $client)
    {
        if ($client->loan_manager_id !== Auth::user()->loanManager->id) {
            abort(403, 'Unauthorized action.');
        }

        $transactions = collect();

        foreach ($client->loans()->with('payments')->get() as $loan) {
            $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
            $transactions->push((object)[
                'date' => $loan->start_date,
                'description' => "Loan Disbursed (ID: {$loan->id})",
                'debit' => $loan->principal_amount + $totalInterest,
                'credit' => 0,
            ]);

            foreach ($loan->payments as $payment) {
                $transactions->push((object)[
                    'date' => $payment->payment_date,
                    'description' => "Payment Received (Receipt: {$payment->id})",
                    'debit' => 0,
                    'credit' => $payment->amount_paid,
                ]);
            }
        }

        return view('loan-manager.clients.ledger', [
            'client' => $client,
            'transactions' => $transactions->sortBy('date'),
        ]);
    }
}