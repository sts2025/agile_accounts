@extends('layouts.manager')

@section('title', $isNew ? 'Add Product' : 'Edit Product')

@section('content')
<div class="container-fluid px-0">

    @php
        $typeLabel = ['loan' => 'Loan', 'savings' => 'Savings', 'shares' => 'Share', 'fixed_deposit' => 'Fixed Deposit'][$type] ?? ucfirst($type);
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">
            {{ $isNew ? 'Add' : 'Edit' }} {{ $typeLabel }} Product
        </h1>
        <a href="{{ route('mfi.products.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Products
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-body p-4 bg-light">
            <form method="POST" action="{{ $isNew ? route('mfi.products.store') : route('mfi.products.update', $product->id) }}">
                @csrf
                @if(!$isNew) @method('PUT') @endif
                <input type="hidden" name="product_type" value="{{ $type }}">

                <div class="mb-3">
                    <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="{{ old('name', $product->name) }}"
                           placeholder="{{ $type === 'loan' ? 'e.g. Boda Boda Loan' : 'e.g. Weekly Savings' }}">
                </div>

                @if($type === 'loan')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Collateral Required (% of principal)</label>
                        <div class="input-group">
                            <input type="number" step="1" min="0" max="500" name="collateral_ratio_percent" class="form-control"
                                   value="{{ old('collateral_ratio_percent', $product->exists ? $product->collateral_ratio * 100 : 30) }}">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted d-block mt-1">
                            A client must have this percentage of the requested loan amount, unlocked, in an active savings
                            account before the loan can be disbursed. Standard default is 30%.
                        </small>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="requires_guarantor" value="1" id="requiresGuarantor"
                               {{ old('requires_guarantor', $product->requires_guarantor ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="requiresGuarantor">Require a guarantor for this product</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Compulsory Savings Split (% of each repayment)</label>
                        <div class="input-group">
                            <input type="number" step="1" min="0" max="100" name="compulsory_savings_percent" class="form-control"
                                   value="{{ old('compulsory_savings_percent', $product->exists ? $product->compulsory_savings_percent : 0) }}">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Optional. Every time a client makes a cash repayment on this loan product, this percentage of the
                            amount paid is automatically deposited into their savings account, in addition to the repayment. Leave at 0 to disable.
                        </small>
                    </div>
                @elseif($type === 'shares')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Value per Share</label>
                        <input type="number" step="1" min="1" name="share_value" class="form-control"
                               value="{{ old('share_value', $product->exists ? $product->share_value : 1000) }}">
                        <small class="text-muted d-block mt-1">
                            The fixed price of one share. Members buy whole shares at this price; dividends are paid
                            out proportional to shares held.
                        </small>
                    </div>
                @elseif($type === 'fixed_deposit')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Interest Rate for Full Term (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="interest_rate" class="form-control"
                               value="{{ old('interest_rate', $product->interest_rate ?? 0) }}">
                        <small class="text-muted d-block mt-1">Total interest paid if held to maturity (not annualized).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Term Length (months)</label>
                        <input type="number" step="1" min="1" max="120" name="term_months" class="form-control"
                               value="{{ old('term_months', $product->exists ? $product->term_months : 12) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Early Withdrawal Penalty (% of interest forfeited)</label>
                        <input type="number" step="1" min="0" max="100" name="early_withdrawal_penalty_percent" class="form-control"
                               value="{{ old('early_withdrawal_penalty_percent', $product->exists ? $product->early_withdrawal_penalty_percent : 10) }}">
                        <small class="text-muted d-block mt-1">Applied to the earned interest if a member closes the deposit before maturity. Principal is never at risk.</small>
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label fw-bold">Annual Interest Rate Paid to Saver (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="interest_rate" class="form-control"
                               value="{{ old('interest_rate', $product->interest_rate ?? 0) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Minimum Balance</label>
                        <input type="number" step="0.01" min="0" name="minimum_balance" class="form-control"
                               value="{{ old('minimum_balance', $product->minimum_balance ?? 0) }}">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_compulsory" value="1" id="isCompulsory"
                               {{ old('is_compulsory', $product->is_compulsory ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isCompulsory">Compulsory savings (required before/alongside a loan)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="allow_withdrawals" value="1" id="allowWithdrawals"
                               {{ old('allow_withdrawals', $product->allow_withdrawals ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="allowWithdrawals">Allow free withdrawals</label>
                    </div>
                @endif

                <div class="d-flex gap-2 pt-2 border-top mt-3">
                    <button type="submit" class="btn btn-{{ $type === 'loan' ? 'primary' : 'success' }} px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Save Product
                    </button>
                    <a href="{{ route('mfi.products.index') }}" class="btn btn-light fw-bold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
