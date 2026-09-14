@extends('layouts.manager')

@section('title', 'Edit Loan Application')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h1 class="h4 mb-1">Edit Loan Application</h1>
                    <p class="text-muted mb-4">For: <strong>{{ $loan->client->name }}</strong></p>

                    <div class="alert alert-info small">
                        This form is only for editing an application before it's approved. Once a loan has been approved or disbursed, use <strong>Reschedule</strong>, <strong>Write Off</strong>, or <strong>Reverse Disbursement</strong> from the loan's page instead — those keep a proper audit trail and update the ledger correctly; editing the raw figures here after money has moved would not.
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

                    <form method="POST" action="{{ route('loans.update', $loan->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="principal_amount" class="form-label">Principal Amount ({{ \App\Models\LoanManager::getCurrency() }})</label>
                            <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" value="{{ old('principal_amount', $loan->principal_amount) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                                <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" value="{{ old('interest_rate', $loan->interest_rate) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="term" class="form-label">Term (Periods)</label>
                                <input type="number" class="form-control" id="term" name="term" value="{{ old('term', $loan->term) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="repayment_frequency" class="form-label">Frequency</label>
                                <select class="form-select" id="repayment_frequency" name="repayment_frequency" required>
                                    @foreach(['Monthly', 'Weekly', 'Daily'] as $freq)
                                        <option value="{{ $freq }}" {{ old('repayment_frequency', $loan->repayment_frequency) === $freq ? 'selected' : '' }}>{{ $freq }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="start_date" class="form-label">Loan Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', \Carbon\Carbon::parse($loan->start_date)->format('Y-m-d')) }}" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
