@extends('layouts.app')

@section('title', 'Create New Loan')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Create a New Loan</h1>
            <hr>

            <form method="POST" action="{{ route('loans.store') }}">
                @csrf
                
                {{-- LOAN DETAILS SECTION --}}
                <h4 class="mb-3">Loan Details</h4>
                <div class="mb-3">
                    <label for="client_id" class="form-label">Select Client</label>
                    <select class="form-control" name="client_id" required>
                        <option value="">-- Please choose a client --</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Principal Amount (UGX)</label><input type="number" step="0.01" class="form-control" name="principal_amount" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Processing Fee (UGX)</label><input type="number" step="0.01" class="form-control" name="processing_fee" value="0"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Interest Rate (%)</label><input type="number" step="0.01" class="form-control" name="interest_rate" required></div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="term" class="form-label">Term</label>
                        <input type="number" class="form-control" name="term" placeholder="e.g., 3, 6, 12" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="repayment_frequency" class="form-label">Frequency</label>
                        <select class="form-select" name="repayment_frequency" required>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3"><label class="form-label">Loan Start Date</label><input type="date" class="form-control" name="start_date" required></div>
                <hr>

                {{-- GUARANTOR DETAILS SECTION --}}
                <h4 class="mb-3 mt-4">Guarantor Details (Optional)</h4>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="guarantor_first_name"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Last Name</label><input type="text" class="form-control" name="guarantor_last_name"></div>
                </div>
                <div class="mb-3"><label class="form-label">Phone Number</label><input type="text" class="form-control" name="guarantor_phone_number"></div>
                <div class="mb-3"><label class="form-label">Address</label><input type="text" class="form-control" name="guarantor_address"></div>
                <div class="mb-3"><label class="form-label">Occupation</label><input type="text" class="form-control" name="guarantor_occupation"></div>
                <div class="mb-3"><label class="form-label">Relationship to Client</label><input type="text" class="form-control" name="guarantor_relationship" placeholder="e.g., Brother, Friend, Co-worker"></div>
                <hr>

                {{-- COLLATERAL DETAILS SECTION --}}
                <h4 class="mb-3 mt-4">Collateral Details (Optional)</h4>
                <div class="mb-3"><label class="form-label">Type of Collateral</label><input type="text" class="form-control" name="collateral_type" placeholder="e.g., Land Title, Vehicle Logbook"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="collateral_description" rows="2" placeholder="e.g., Toyota Corolla, Reg No. UBA 123X"></textarea></div>
                <div class="mb-3"><label class="form-label">Valuation Amount (UGX)</label><input type="number" step="0.01" class="form-control" name="collateral_valuation_amount"></div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save New Loan</button>
                    <a href="{{ route('loans.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection