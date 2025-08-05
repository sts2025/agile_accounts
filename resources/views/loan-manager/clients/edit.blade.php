@extends('layouts.manager')

@section('title', 'Edit Client')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>Edit Client: {{ $client->name }}</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('clients.update', $client->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" value="{{ $client->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone_number" value="{{ $client->phone_number }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3">{{ $client->address }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Business / Occupation (Optional)</label>
                    <input type="text" class="form-control" name="business_occupation" value="{{ $client->business_occupation }}">
                </div>

                <button type="submit" class="btn btn-primary">Update Client</button>
                <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection