<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start with the query that we know works
        $query = Auth::user()->clients();

        // Check if a search term was submitted
        // In index() method
if ($search = $request->input('search')) {
    $searchTerm = strtolower($search);
    // The query now only searches the name column
    $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
}
        



        // Now, execute the final query
        $clients = $query->latest()->get();

        return view('loan-manager.clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('loan-manager.clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            //'email' => 'required|string|email|max:255|unique:clients',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        Auth::user()->clients()->create($validatedData);

        return redirect()->route('clients.index')->with('status', 'Client has been added successfully!');
    }

    /**
     * Display the specified resource.
     * We are not using this in our current design, but it's here for completeness.
     */
    public function show(Client $client)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        if (Auth::id() !== $client->loan_manager_id) {
            abort(403);
        }
        return view('loan-manager.clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        if (Auth::id() !== $client->loan_manager_id) {
            abort(403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            //'email' => ['required', 'string', 'email', 'max:255', Rule::unique('clients')->ignore($client->id)],
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client->update($validatedData);

        return redirect()->route('clients.index')->with('status', 'Client details have been updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        if (Auth::id() !== $client->loan_manager_id) {
            abort(403);
        }
        $client->delete();
        return redirect()->route('clients.index')->with('status', 'Client has been deleted successfully!');
    }
}