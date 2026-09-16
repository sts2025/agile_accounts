<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    private function managerId(): int
    {
        $user = Auth::user();
        $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;

        return $manager->id;
    }

    public function index()
    {
        $managerId = $this->managerId();

        $branches = Branch::where('loan_manager_id', $managerId)
            ->withCount(['staff', 'clients', 'loans'])
            ->orderBy('name')
            ->get();

        return view('loan-manager.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $managerId = $this->managerId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
        ]);

        $validated['loan_manager_id'] = $managerId;
        $validated['is_active'] = true;

        Branch::create($validated);

        return back()->with('success', 'Branch added successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        if ($branch->loan_manager_id !== $this->managerId()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
        ]);

        $branch->update($validated);

        return back()->with('success', 'Branch updated.');
    }

    public function toggle(Branch $branch)
    {
        if ($branch->loan_manager_id !== $this->managerId()) {
            abort(403);
        }

        $branch->update(['is_active' => !$branch->is_active]);

        return back()->with('success', $branch->is_active ? 'Branch re-activated.' : 'Branch deactivated. It stays selectable on existing records but won\'t appear in "add new" dropdowns.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->loan_manager_id !== $this->managerId()) {
            abort(403);
        }

        if ($branch->staff()->exists() || $branch->clients()->exists() || $branch->loans()->exists()) {
            return back()->with('error', 'This branch has staff, clients, or loans assigned to it. Deactivate it instead of deleting, or reassign those records first.');
        }

        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }
}
