@extends('layouts.manager')

@section('title', 'Business Settings')

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-dark">Business Profile</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('manager.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                
                {{-- 1. FINANCIAL CONFIGURATION (OPENING BALANCE) --}}
                <div class="card shadow mb-4 border-left-success">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-success">Financial Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold text-dark">Opening Balance (Your Starting Cash Hand)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">
                                        {{ $manager->currency_symbol ?? 'UGX' }}
                                    </span>
                                    <input type="number" step="0.01" name="opening_balance" 
                                           class="form-control form-control-lg fw-bold text-success" 
                                           value="{{ old('opening_balance', $manager->opening_balance ?? 0) }}">
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Enter the amount of cash you have at hand <strong>before</strong> recording any loans or payments in this system. This will tally with your dashboard "Opening Balance".
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. COMPANY DETAILS --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-primary">Company Details for Receipts</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Company / Business Name</label>
                            <input type="text" name="company_name" class="form-control" 
                                   value="{{ old('company_name', $manager->company_name) }}" required>
                            <small class="text-muted">This will appear at the top of every receipt.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Business Phone</label>
                                <input type="text" name="company_phone" class="form-control" 
                                       value="{{ old('company_phone', $manager->company_phone) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Business Email (Optional)</label>
                                <input type="email" name="company_email" class="form-control" 
                                       value="{{ old('company_email', $manager->company_email) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Address / Location</label>
                            <textarea name="company_address" class="form-control" rows="3" required>{{ old('company_address', $manager->company_address) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Company Logo (Optional)</label>
                            <input type="file" name="company_logo" class="form-control">
                            @if($manager->company_logo)
                                <div class="mt-2 p-2 border rounded d-inline-block">
                                    <img src="{{ asset('storage/' . $manager->company_logo) }}" alt="Logo" style="height: 50px;">
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>

            {{-- 3. RECEIPT PREVIEW --}}
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">Receipt Preview</h6>
                    </div>
                    <div class="card-body text-center bg-light py-5">
                        <div class="receipt-mockup border p-3 bg-white shadow-sm mx-auto" style="max-width: 250px; font-family: monospace;">
                            @if($manager->company_logo)
                                <img src="{{ asset('storage/' . $manager->company_logo) }}" style="max-width: 60px;" class="mb-2">
                            @else
                                <i class="fas fa-store fa-2x text-muted mb-2"></i>
                            @endif
                            <h6 class="fw-bold mb-1 text-uppercase">{{ $manager->company_name ?? 'Your Company' }}</h6>
                            <p class="small text-muted mb-0" style="font-size: 0.7rem;">
                                {{ $manager->company_address ?? 'Address Line 1' }}<br>
                                Tel: {{ $manager->company_phone ?? '0700 000 000' }}
                            </p>
                            <hr style="border-top: 1px dashed #000;">
                            <p class="small fw-bold mb-1">Receipt #: RCP-EXAMPLE</p>
                            <p class="small fw-bold">Amount: {{ $manager->currency_symbol ?? 'UGX' }} 50,000</p>
                            <hr style="border-top: 1px dashed #000;">
                            <p class="small text-muted" style="font-size: 0.6rem;">This is how your header will look.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection