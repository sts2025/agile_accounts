@extends('layouts.manager')

@section('title', 'Edit Client')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Edit Client: {{ $client->name }}</h1>
        <a href="{{ route('clients.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Clients
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 800px;">
        <div class="card-body p-4">

            @if($errors->any())
                <div class="alert alert-danger shadow-sm border-start border-danger border-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('clients.update', $client->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $client->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $client->phone_number) }}" required>
                    </div>
                </div>

                {{-- NEW FIELDS ADDED HERE: NIN & DOB --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">National ID (NIN) <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="national_id" class="form-control" value="{{ old('national_id', $client->national_id) }}" placeholder="e.g. CM12345678">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Date of Birth <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">Address</label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $client->address) }}</textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Business / Occupation <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="business_occupation" class="form-control" value="{{ old('business_occupation', $client->business_occupation) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Email Address <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                    </div>
                </div>

                <div class="d-flex gap-2 border-top pt-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Update Client
                    </button>
                    <a href="{{ route('clients.index') }}" class="btn btn-light fw-bold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection