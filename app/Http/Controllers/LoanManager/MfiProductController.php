<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\MfiProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MfiProductController extends Controller
{
    /**
     * List every loan/savings product this manager has configured.
     */
    public function index()
    {
        $managerId = Auth::user()->loanManager->id;

        $products = MfiProduct::where('loan_manager_id', $managerId)
            ->orderBy('product_type')
            ->orderBy('name')
            ->get();

        $loanProducts = $products->where('product_type', 'loan');
        $savingsProducts = $products->where('product_type', 'savings');
        $shareProducts = $products->where('product_type', 'shares');
        $fixedDepositProducts = $products->where('product_type', 'fixed_deposit');

        return view('loan-manager.mfi.products.index', compact('loanProducts', 'savingsProducts', 'shareProducts', 'fixedDepositProducts'));
    }

    /**
     * Show the form for a new product. ?type=loan, savings, shares, or fixed_deposit.
     */
    public function create(Request $request)
    {
        $type = $request->query('type', 'loan');
        abort_unless(in_array($type, ['loan', 'savings', 'shares', 'fixed_deposit']), 404);

        $product = new MfiProduct(['product_type' => $type]);

        return view('loan-manager.mfi.products.form', [
            'product' => $product,
            'type' => $type,
            'isNew' => true,
        ]);
    }

    public function store(Request $request)
    {
        $managerId = Auth::user()->loanManager->id;

        $validated = $this->validateProduct($request);

        MfiProduct::create([
            'loan_manager_id' => $managerId,
            'name' => $validated['name'],
            'product_type' => $validated['product_type'],
            'interest_rate' => $validated['interest_rate'] ?? 0,
            'rules' => $this->buildRules($request, $validated['product_type']),
            'is_active' => true,
        ]);

        return redirect()->route('mfi.products.index')->with('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $product = MfiProduct::where('loan_manager_id', $managerId)->findOrFail($id);

        return view('loan-manager.mfi.products.form', [
            'product' => $product,
            'type' => $product->product_type,
            'isNew' => false,
        ]);
    }

    public function update(Request $request, $id)
    {
        $managerId = Auth::user()->loanManager->id;
        $product = MfiProduct::where('loan_manager_id', $managerId)->findOrFail($id);

        $validated = $this->validateProduct($request, $product->product_type);

        $product->update([
            'name' => $validated['name'],
            'interest_rate' => $validated['interest_rate'] ?? 0,
            'rules' => $this->buildRules($request, $product->product_type),
        ]);

        return redirect()->route('mfi.products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Activate/deactivate instead of deleting — existing savings/loan
     * accounts hold a foreign key to this product, so removing it outright
     * would cascade-delete real client data. Deactivating just hides it
     * from future account-opening/loan-creation dropdowns.
     */
    public function toggle($id)
    {
        $managerId = Auth::user()->loanManager->id;
        $product = MfiProduct::where('loan_manager_id', $managerId)->findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        return back()->with('success', $product->is_active ? 'Product activated.' : 'Product deactivated.');
    }

    private function validateProduct(Request $request, ?string $lockedType = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'collateral_ratio_percent' => 'nullable|numeric|min:0|max:500',
            'requires_guarantor' => 'nullable|boolean',
            'compulsory_savings_percent' => 'nullable|numeric|min:0|max:100',
            'minimum_balance' => 'nullable|numeric|min:0',
            'is_compulsory' => 'nullable|boolean',
            'allow_withdrawals' => 'nullable|boolean',
            'share_value' => 'nullable|numeric|min:1',
            'term_months' => 'nullable|integer|min:1|max:120',
            'early_withdrawal_penalty_percent' => 'nullable|numeric|min:0|max:100',
        ];

        if ($lockedType === null) {
            $rules['product_type'] = 'required|in:loan,savings,shares,fixed_deposit';
        }

        $validated = $request->validate($rules);
        $validated['product_type'] = $lockedType ?? $validated['product_type'];

        return $validated;
    }

    private function buildRules(Request $request, string $type): array
    {
        if ($type === 'loan') {
            // Form collects a human-friendly percentage (e.g. 30); stored as a fraction (0.30).
            $percent = $request->input('collateral_ratio_percent');
            return [
                'collateral_ratio' => $percent !== null ? ((float) $percent) / 100 : 0.30,
                'requires_guarantor' => $request->boolean('requires_guarantor'),
                'compulsory_savings_percent' => (float) ($request->input('compulsory_savings_percent') ?? 0),
            ];
        }

        if ($type === 'shares') {
            return [
                'share_value' => (float) ($request->input('share_value') ?? 1000),
            ];
        }

        if ($type === 'fixed_deposit') {
            return [
                'term_months' => (int) ($request->input('term_months') ?? 12),
                'early_withdrawal_penalty_percent' => (float) ($request->input('early_withdrawal_penalty_percent') ?? 10),
            ];
        }

        return [
            'minimum_balance' => (float) ($request->input('minimum_balance') ?? 0),
            'is_compulsory' => $request->boolean('is_compulsory'),
            'allow_withdrawals' => $request->boolean('allow_withdrawals', true),
        ];
    }
}
