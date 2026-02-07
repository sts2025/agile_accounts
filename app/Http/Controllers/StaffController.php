<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * List all cashiers for this manager.
     */
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;
        
        // Find users linked to this manager
        $staff = User::where('loan_manager_id', $managerId)
                     ->where('role', 'cashier')
                     ->get();
                     
        return view('loan-manager.staff.index', compact('staff'));
    }

    /**
     * Create a new cashier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $managerId = Auth::user()->loanManager->id;

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'cashier',
            'loan_manager_id' => $managerId, // Link to your company
        ]);

        return back()->with('success', 'New cashier account created successfully.');
    }

    /**
     * Delete a cashier.
     */
    public function destroy($id)
    {
        $managerId = Auth::user()->loanManager->id;
        
        $user = User::where('id', $id)
                    ->where('loan_manager_id', $managerId)
                    ->firstOrFail();
                    
        $user->delete();

        return back()->with('success', 'Cashier account removed.');
    }
}