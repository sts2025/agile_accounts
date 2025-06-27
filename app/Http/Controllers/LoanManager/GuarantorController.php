<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Guarantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GuarantorController extends Controller
{
    /**
     * Store a newly created guarantor in storage.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming data from the form
        $validatedData = $request->validate([
            // Security check: ensure the loan exists and belongs to the current manager
            'loan_id' => [
                'required',
                Rule::exists('loans', 'id')->where('loan_manager_id', Auth::id())
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'nin' => 'nullable|string|max:50',
            'address' => 'required|string|max:255',
            'relationship_to_borrower' => 'required|string|max:100',
        ]);

        // 2. Create the guarantor record
        Guarantor::create($validatedData);

        // 3. Redirect back to the loan details page with a success message
        return redirect()->route('loans.show', $validatedData['loan_id'])
                         ->with('status', 'Guarantor has been added successfully!');
    }
}