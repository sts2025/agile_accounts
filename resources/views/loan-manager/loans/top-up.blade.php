<?php $currency = \App\Models\LoanManager::getCurrency(); ?>
@extends('layouts.manager')

@section('title', 'Top Up Loan #' . $loan->id)

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Top Up Loan #{{ $loan->id }} — {{ $loan->client->name ?? 'Client' }}</h1>
        <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-secondary shadow-sm">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info">
        <strong>How this works:</strong> a new loan is created for this client. When it's approved and disbursed, loan #{{ $loan->id }}'s remaining balance is automatically settled out of the new principal, and only the difference is actually paid out to the client — they don't need to fully repay the old loan first.
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-bold">Old Loan #{{ $loan->id }} Balance</div>
                    <div class="h4">{{ $currency }} {{ number_format($outstandingBalance, 2) }}</div>
                    <div class="text-muted small">(principal + interest still owed)</div>
                </div>
            </div>

            <form method="POST" action="{{ route('loans.top-up.store', $loan->id) }}">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">New Principal Amount ({{ $currency }}) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="{{ $outstandingBalance }}" name="principal_amount" class="form-control" value="{{ old('principal_amount', $outstandingBalance) }}" required>
                        <small class="text-muted">Must be at least the old balance shown above.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Processing Fee ({{ $currency }})</label>
                        <input type="number" step="0.01" name="processing_fee" class="form-control" value="{{ old('processing_fee', 0) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Interest Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="interest_rate" class="form-control" value="{{ old('interest_rate', $loan->interest_rate) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Interest Method</label>
                        <select name="interest_method" class="form-control">
                            <option value="flat" {{ old('interest_method', 'flat') == 'flat' ? 'selected' : '' }}>Flat Rate</option>
                            <option value="reducing_balance" {{ old('interest_method') == 'reducing_balance' ? 'selected' : '' }}>Reducing Balance</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Term (Periods) <span class="text-danger">*</span></label>
                        <input type="number" name="term" class="form-control" value="{{ old('term', $loan->term) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select name="repayment_frequency" class="form-control" required>
                            <option value="Monthly" {{ old('repayment_frequency', $loan->repayment_frequency) == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="Weekly" {{ old('repayment_frequency', $loan->repayment_frequency) == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="Daily" {{ old('repayment_frequency', $loan->repayment_frequency) == 'Daily' ? 'selected' : '' }}>Daily</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->toDateString()) }}" required>
                </div>

                <button type="submit" class="btn btn-primary">Create Top-Up Application</button>
            </form>
        </div>
    </div>
</div>
@endsection
