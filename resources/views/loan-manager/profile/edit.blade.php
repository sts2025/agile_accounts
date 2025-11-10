@extends('layouts.manager')
@section('title', 'Company Profile')
@section('content')
    <div class="card">
        <div class="card-header"><h1>Company Profile & Settings</h1></div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $manager->company_name) }}">
                </div>
                <div class="mb-3">
                    <label for="company_phone" class="form-label">Company Phone</label>
                    <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ old('company_phone', $manager->company_phone) }}">
                </div>
                <div class="mb-4">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input class="form-control" type="file" id="company_logo" name="company_logo">
                </div>
                @if ($manager->company_logo_path)
                    <div class="mb-4">
                        <p><strong>Current Logo:</strong></p>
                        <img src="{{ asset('storage/' . $manager->company_logo_path) }}" alt="Current Logo" style="max-height: 80px;">
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
@endsection