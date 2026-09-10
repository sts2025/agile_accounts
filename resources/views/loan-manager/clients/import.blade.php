@extends('layouts.manager')

@section('title', 'Import Clients')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Import Clients (CSV)</h1>
    <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Back to Clients</a>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <p class="mb-2">Upload a CSV file to create multiple clients at once. Each row is validated the same way as the "Add New Client" form — required fields are <strong>name</strong>, <strong>phone_number</strong>, and <strong>address</strong>. Rows with errors are skipped and reported; valid rows are still created.</p>
        <a href="{{ route('clients.import.template') }}" class="btn btn-sm btn-outline-primary mb-3">
            <i class="fas fa-download me-1"></i> Download CSV Template
        </a>

        <form method="POST" action="{{ route('clients.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Expected Columns</div>
    <div class="card-body">
        <p class="small text-muted mb-2">First row must be a header row with these column names (order doesn't matter, extra columns are ignored):</p>
        <code>name, phone_number, address, national_id, email, date_of_birth, gender, business_occupation, next_of_kin_name, next_of_kin_phone, next_of_kin_relationship, client_type, business_name, business_registration_number</code>
    </div>
</div>
@endsection
