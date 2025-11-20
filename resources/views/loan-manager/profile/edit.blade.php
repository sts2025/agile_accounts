@extends('layouts.manager')
@section('title', 'Company Profile')
@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Company Profile & Personal Settings</h1>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            
            <!-- CRUCIAL FIX: Added @method('PATCH') and ensured enctype is present -->
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <h5 class="mb-3 text-primary">Personal Details</h5>
                <div class="row">
                    <!-- Assuming User details (Name, Email, Phone, Address) are accessible via the $manager->user relationship -->
                    
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $manager->user->name ?? '') }}" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $manager->user->email ?? '') }}" required>
                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Personal Phone</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $manager->user->phone_number ?? '') }}" required>
                        @error('phone_number') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $manager->user->address ?? '') }}" required>
                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <h5 class="mt-4 mb-3 text-primary">Company Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $manager->company_name) }}">
                        @error('company_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="company_phone" class="form-label">Company Phone</label>
                        <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ old('company_phone', $manager->company_phone) }}">
                        @error('company_phone') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="currency_symbol" class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $manager->currency_symbol) }}" required>
                        @error('currency_symbol') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <h5 class="mt-4 mb-3 text-primary">Logo Upload</h5>
                <div class="mb-4">
                    <label for="company_logo" class="form-label">Company Logo (Max 2MB, JPG/PNG/GIF)</label>
                    <input class="form-control" type="file" id="company_logo" name="company_logo">
                    @error('company_logo') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                
                @if ($manager->company_logo_path)
                    <div class="mb-4">
                        <p><strong>Current Logo:</strong></p>
                        <img src="{{ asset('storage/' . $manager->company_logo_path) }}" alt="Current Logo" class="img-thumbnail" style="max-height: 80px;">
                    </div>
                @endif
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
@endsection