@extends('layouts.app')

@section('title', 'Edit Loan')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edit Loan for: {{ $loan->client->name }}</h1>
            <hr>

            <form method="POST" action="{{ route('loans.update', $loan->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="principal_amount" class="form-label">Principal Amount (UGX)</label>
                    <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" value="{{ $loan->principal_amount }}" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" value="{{ $loan->interest_rate }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="term" class="form-label">Term (in months)</label>
                        <input type="number" class="form-control" id="term" name="term" value="{{ $loan->term }}" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="start_date" class="form-label">Loan Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $loan->start_date }}" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Loan Status</label>
                    <select class="form-select" name="status">
                        <option value="pending" {{ $loan->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ $loan->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="paid" {{ $loan->status == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="defaulted" {{ $loan->status == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Loan</button>
                <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection