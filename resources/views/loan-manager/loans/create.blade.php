@extends('layouts.app')

@section('title', 'Create New Loan')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Create a New Loan</h1>
            <hr>

            <form method="POST" action="{{ route('loans.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="client_id" class="form-label">Select Client</label>
                    <select class="form-control" id="client_id" name="client_id" required>
                        <option value="">-- Please choose a client --</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="principal_amount" class="form-label">Principal Amount (UGX)</label>
                    <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="term" class="form-label">Term (in months)</label>
                        <input type="number" class="form-control" id="term" name="term" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="start_date" class="form-label">Loan Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>

                <button type="submit" class="btn btn-primary">Save Loan</button>
                <a href="{{ route('loans.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection