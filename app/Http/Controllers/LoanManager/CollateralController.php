<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Collateral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CollateralController extends Controller
{
    /**
     * Store a newly created collateral in storage.
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
            'collateral_type' => 'required|string|max:100',
            'description' => 'required|string',
            'valuation_amount' => 'required|numeric|min:0',
            'document_details' => 'nullable|string',
        ]);

        // 2. Create the collateral record
        Collateral::create($validatedData);

        // 3. Redirect back to the loan details page with a success message
        return redirect()->route('loans.show', $validatedData['loan_id'])
                         ->with('status', 'Collateral has been added successfully!');
    }
}