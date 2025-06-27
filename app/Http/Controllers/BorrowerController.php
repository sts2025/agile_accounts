<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BorrowerController extends Controller
{
    /**
     * Display a listing of the borrowers for the authenticated loan manager.
     */
    public function index(Request $request)
    {
        $loanManagerId = Auth::user()->loanManager->id;
        $borrowers = Borrower::where('loan_manager_id', $loanManagerId)->get();
        return response()->json($borrowers);
    }

    /**
     * Store a newly created borrower in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nin' => 'required|string|max:50|unique:borrowers',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'occupation' => 'required|string|max:255',
        ]);

        $borrower = Borrower::create([
            'loan_manager_id' => Auth::user()->loanManager->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'nin' => $request->nin,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'occupation' => $request->occupation,
        ]);

        return response()->json([
            'message' => 'Borrower created successfully.',
            'borrower' => $borrower
        ], 201);
    }

    /**
     * Display the specified borrower.
     */
    public function show(Borrower $borrower)
    {
        // Ensure the loan manager owns this borrower
        if ($borrower->loan_manager_id !== Auth::user()->loanManager->id) {
            return response()->json(['message' => 'Unauthorized access to this borrower.'], 403);
        }
        return response()->json($borrower);
    }

    /**
     * Update the specified borrower in storage.
     */
    public function update(Request $request, Borrower $borrower)
    {
        // Ensure the loan manager owns this borrower
        if ($borrower->loan_manager_id !== Auth::user()->loanManager->id) {
            return response()->json(['message' => 'Unauthorized access to update this borrower.'], 403);
        }

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'nin' => 'sometimes|required|string|max:50|unique:borrowers,nin,' . $borrower->id, // Exclude current borrower's ID
            'phone_number' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string|max:255',
            'date_of_birth' => 'sometimes|required|date',
            'gender' => 'sometimes|required|in:male,female,other',
            'occupation' => 'sometimes|required|string|max:255',
        ]);

        $borrower->update($request->all());

        return response()->json([
            'message' => 'Borrower updated successfully.',
            'borrower' => $borrower
        ]);
    }

    /**
     * Remove the specified borrower from storage.
     */
    public function destroy(Borrower $borrower)
    {
        // Ensure the loan manager owns this borrower
        if ($borrower->loan_manager_id !== Auth::user()->loanManager->id) {
            return response()->json(['message' => 'Unauthorized access to delete this borrower.'], 403);
        }

        // Add logic to check if there are associated loans before deleting, or cascade delete them
        // For simplicity now, we'll just delete. In a real app, you might prevent deletion if loans exist.
        $borrower->delete();

        return response()->json(['message' => 'Borrower deleted successfully.']);
    }
}