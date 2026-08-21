<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientGroupController extends Controller
{
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        $groups = ClientGroup::where('loan_manager_id', $managerId)
            ->withCount(['members', 'loans'])
            ->latest()
            ->get();

        return view('loan-manager.client-groups.index', compact('groups'));
    }

    public function create()
    {
        $managerId = Auth::user()->loanManager->id;
        $clients = Client::where('loan_manager_id', $managerId)->orderBy('name')->get();

        return view('loan-manager.client-groups.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'members' => 'nullable|array',
            'members.*' => ['integer', \Illuminate\Validation\Rule::exists('clients', 'id')->where('loan_manager_id', $managerId)],
        ]);

        $group = ClientGroup::create([
            'loan_manager_id' => $managerId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['members'])) {
            $group->members()->sync($validated['members']);
        }

        return redirect()->route('client-groups.show', $group->id)->with('success', 'Group created successfully.');
    }

    public function show($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $group = ClientGroup::where('loan_manager_id', $managerId)
            ->with(['members', 'loans.client'])
            ->findOrFail($id);

        return view('loan-manager.client-groups.show', compact('group'));
    }

    public function edit($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $group = ClientGroup::where('loan_manager_id', $managerId)->with('members')->findOrFail($id);
        $clients = Client::where('loan_manager_id', $managerId)->orderBy('name')->get();
        $memberIds = $group->members->pluck('id')->toArray();

        return view('loan-manager.client-groups.edit', compact('group', 'clients', 'memberIds'));
    }

    public function update(Request $request, $id)
    {
        $managerId = Auth::user()->loanManager->id;
        $group = ClientGroup::where('loan_manager_id', $managerId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'members' => 'nullable|array',
            'members.*' => ['integer', \Illuminate\Validation\Rule::exists('clients', 'id')->where('loan_manager_id', $managerId)],
        ]);

        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $group->members()->sync($validated['members'] ?? []);

        return redirect()->route('client-groups.show', $group->id)->with('success', 'Group updated successfully.');
    }

    public function destroy($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $group = ClientGroup::where('loan_manager_id', $managerId)->withCount('loans')->findOrFail($id);

        if ($group->loans_count > 0) {
            return back()->with('error', 'This group has loans on record and cannot be deleted. Remove or reassign those loans first.');
        }

        $group->members()->detach();
        $group->delete();

        return redirect()->route('client-groups.index')->with('success', 'Group deleted.');
    }
}
