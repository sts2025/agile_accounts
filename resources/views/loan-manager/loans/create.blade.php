<?php
// Retrieve the dynamic currency symbol from the authenticated user's manager settings
$currency = optional(auth()->user()->manager)->currency_symbol ?? 'UGX';
?>
@extends('layouts.manager')

@section('title', 'Create New Loan')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Create a New Loan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Loan Details</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('loans.store') }}">
                @csrf
                
                {{-- LOAN DETAILS SECTION --}}
                @if($clientGroups->isNotEmpty())
                <div class="form-group mb-3">
                    <label for="client_group_id">Client Group <span class="text-muted small">(Optional — for group/joint-liability loans)</span></label>
                    <select class="form-control" id="client_group_id" name="client_group_id">
                        <option value="">-- Not a group loan --</option>
                        @foreach ($clientGroups as $group)
                            <option value="{{ $group->id }}"
                                data-members="{{ $group->members->pluck('id')->implode(',') }}"
                                {{ (old('client_group_id', $selectedGroupId) == $group->id) ? 'selected' : '' }}>
                                {{ $group->name }} ({{ $group->members->count() }} members)
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1">Selecting a group will filter the client list below to its members. Choose the member who will sign as the representative/borrower of record.</small>
                    @error('client_group_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                @endif

                <div class="form-group mb-3">
                    <label for="client_id">Select Client <span class="text-danger">*</span></label>
                    <select class="form-control" id="client_id" name="client_id" required>
                        <option value="">-- Please choose a client --</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }} {{ $client->is_blacklisted ? 'disabled' : '' }}>
                                {{ $client->name }} ({{ $client->phone ?? 'N/A' }}){{ $client->is_blacklisted ? ' — BLACKLISTED' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                @if($loanProducts->isNotEmpty())
                <div class="form-group mb-3">
                    <label for="mfi_loan_product_id">Loan Product</label>
                    <select class="form-control" id="mfi_loan_product_id" name="mfi_loan_product_id">
                        @foreach ($loanProducts as $product)
                            <option value="{{ $product->id }}" {{ old('mfi_loan_product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} — {{ number_format($product->collateral_ratio * 100, 0) }}% savings collateral required
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1">
                        <a href="{{ route('mfi.products.index') }}">Manage loan products</a>
                    </small>
                    @error('mfi_loan_product_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        {{-- FIX: Using dynamic $currency --}}
                        <label for="principal_amount">Principal Amount ({{ $currency }}) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" value="{{ old('principal_amount') }}" required>
                        @error('principal_amount') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        {{-- FIX: Using dynamic $currency --}}
                        <label for="processing_fee">Processing Fee ({{ $currency }})</label>
                        <input type="number" step="0.01" class="form-control" id="processing_fee" name="processing_fee" value="{{ old('processing_fee', 0) }}">
                        @error('processing_fee') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="interest_rate">Interest Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" value="{{ old('interest_rate') }}" required>
                        @error('interest_rate') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="term">Loan Term (Periods) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="term" name="term" value="{{ old('term') }}" placeholder="e.g., 3, 6, 12" required>
                        @error('term') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="repayment_frequency">Frequency <span class="text-danger">*</span></label>
                        <select class="form-control" id="repayment_frequency" name="repayment_frequency" required>
                            <option value="Monthly" {{ old('repayment_frequency') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="Weekly" {{ old('repayment_frequency') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="Daily" {{ old('repayment_frequency') == 'Daily' ? 'selected' : '' }}>Daily</option>
                        </select>
                        @error('repayment_frequency') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="start_date">Loan Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
                    @error('start_date') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <hr>

                {{-- GUARANTOR DETAILS SECTION --}}
                <h4 class="mb-3 mt-4 text-secondary">Guarantor Details (Optional)</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_first_name">First Name</label>
                        <input type="text" class="form-control" id="guarantor_first_name" name="guarantor_first_name" value="{{ old('guarantor_first_name') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_last_name">Last Name</label>
                        <input type="text" class="form-control" id="guarantor_last_name" name="guarantor_last_name" value="{{ old('guarantor_last_name') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_phone_number">Phone Number</label>
                        <input type="text" class="form-control" id="guarantor_phone_number" name="guarantor_phone_number" value="{{ old('guarantor_phone_number') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_occupation">Occupation</label>
                        <input type="text" class="form-control" id="guarantor_occupation" name="guarantor_occupation" value="{{ old('guarantor_occupation') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="guarantor_address">Address</label>
                    <input type="text" class="form-control" id="guarantor_address" name="guarantor_address" value="{{ old('guarantor_address') }}">
                </div>
                <div class="mb-3">
                    <label for="guarantor_relationship">Relationship to Client</label>
                    <input type="text" class="form-control" id="guarantor_relationship" name="guarantor_relationship" value="{{ old('guarantor_relationship') }}" placeholder="e.g., Brother, Friend, Co-worker">
                </div>
                <hr>
                
                {{-- COLLATERAL DETAILS SECTION --}}
                <h4 class="mb-3 mt-4 text-secondary">Collateral Details (Optional)</h4>
                <div class="mb-3">
                    <label for="collateral_type">Type of Collateral</label>
                    <input type="text" class="form-control" id="collateral_type" name="collateral_type" value="{{ old('collateral_type') }}" placeholder="e.g., Land Title, Vehicle Logbook">
                </div>
                <div class="mb-3">
                    <label for="collateral_description">Description</label>
                    <textarea class="form-control" id="collateral_description" name="collateral_description" rows="2" placeholder="e.g., Toyota Corolla, Reg No. UBA 123X">{{ old('collateral_description') }}</textarea>
                </div>
                <div class="mb-3">
                    {{-- FIX: Using dynamic $currency --}}
                    <label for="collateral_valuation_amount">Valuation Amount ({{ $currency }})</label>
                    <input type="number" step="0.01" class="form-control" id="collateral_valuation_amount" name="collateral_valuation_amount" value="{{ old('collateral_valuation_amount') }}">
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-icon-split">
                        <span class="icon text-white-50"><i class="fas fa-save"></i></span>
                        <span class="text">Submit Loan Application</span>
                    </button>
                    <a href="{{ route('loans.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@if($clientGroups->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var groupSelect = document.getElementById('client_group_id');
    var clientSelect = document.getElementById('client_id');
    if (!groupSelect || !clientSelect) return;

    var allOptions = Array.prototype.slice.call(clientSelect.options);

    function applyGroupFilter() {
        var selected = groupSelect.options[groupSelect.selectedIndex];
        var memberIds = selected && selected.value
            ? selected.getAttribute('data-members').split(',').filter(Boolean)
            : null;

        var currentValue = clientSelect.value;
        clientSelect.innerHTML = '';

        allOptions.forEach(function (opt) {
            if (!memberIds || opt.value === '' || memberIds.indexOf(opt.value) !== -1) {
                clientSelect.appendChild(opt.cloneNode(true));
            }
        });

        // Keep the previous selection if it's still valid for this group.
        if (memberIds && memberIds.indexOf(currentValue) !== -1) {
            clientSelect.value = currentValue;
        }
    }

    groupSelect.addEventListener('change', applyGroupFilter);

    // Apply on load in case a group came pre-selected (e.g. via "Issue Group Loan").
    if (groupSelect.value) {
        applyGroupFilter();
    }
});
</script>
@endif
@endsection