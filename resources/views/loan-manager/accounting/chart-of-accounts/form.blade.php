@extends('layouts.manager')

@section('title', $isNew ? 'Add Account' : 'Edit Account')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">{{ $isNew ? 'Add Account' : 'Edit Account' }}</h1>
        <a href="{{ route('chart-of-accounts.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Chart of Accounts
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 600px;">
        <div class="card-body p-4 bg-light">
            @if($errors->any())
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $isNew ? route('chart-of-accounts.store') : route('chart-of-accounts.update', $account->id) }}">
                @csrf
                @unless($isNew) @method('PUT') @endunless

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-dark">Code</label>
                        <input type="text" name="code" class="form-control shadow-sm" value="{{ old('code', $account->code) }}" {{ !$isNew && $account->is_system ? 'readonly' : '' }} required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold text-dark">Name</label>
                        <input type="text" name="name" class="form-control shadow-sm" value="{{ old('name', $account->name) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Type</label>
                    <select name="type" id="accountTypeSelect" class="form-select shadow-sm" {{ !$isNew && $account->is_system ? 'disabled' : '' }} required>
                        @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'] as $value => $label)
                            <option value="{{ $value }}" {{ old('type', $account->type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(!$isNew && $account->is_system)
                        {{-- Disabled selects don't submit their value — resend it as a hidden field. --}}
                        <input type="hidden" name="type" value="{{ $account->type }}">
                        <small class="text-muted d-block mt-1">Standard accounts can't change type — deactivate and add a new one instead if you need something different.</small>
                    @endif
                </div>

                <div class="row" id="bankFields">
                    <div class="col-md-7 mb-3">
                        <label class="form-label fw-bold text-dark">Bank / Till Name <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="bank_name" class="form-control shadow-sm" value="{{ old('bank_name', $account->bank_name) }}" placeholder="e.g. Centenary Bank, Till #2">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label fw-bold text-dark">Account Number <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="external_account_number" class="form-control shadow-sm" value="{{ old('external_account_number', $account->external_account_number) }}">
                    </div>
                    <small class="text-muted d-block mb-3" style="margin-top:-0.75rem;">For an institution with more than one cash point or bank account, so each shows up distinctly.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Description <span class="text-secondary fw-normal">(Optional)</span></label>
                    <textarea name="description" class="form-control shadow-sm" rows="2">{{ old('description', $account->description) }}</textarea>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Save
                    </button>
                    <a href="{{ route('chart-of-accounts.index') }}" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        var typeSelect = document.getElementById('accountTypeSelect');
        var bankFields = document.getElementById('bankFields');

        function toggleBankFields() {
            bankFields.style.display = typeSelect.value === 'asset' ? '' : 'none';
        }
        typeSelect.addEventListener('change', toggleBankFields);
        toggleBankFields();
    })();
</script>
@endpush
@endsection
