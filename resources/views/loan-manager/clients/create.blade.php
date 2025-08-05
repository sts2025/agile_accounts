@extends('layouts.manager')

@section('title', 'Add New Client')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>Add a New Client</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('clients.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone_number" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Business / Occupation (Optional)</label>
                    <input type="text" class="form-control" name="business_occupation">
                </div>

                <button type="submit" class="btn btn-primary">Save Client</button>
                <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection