@extends('layouts.app')

@section('title', 'Company Profile')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Company Profile & Financial Settings</h1>

    @if(session('success'))
        <div class="alert alert-success border-left-success shadow-sm">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="row">
        {{-- LEFT COLUMN: EDIT FORM --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- 1. FINANCIAL SETTINGS (THIS IS THE NEW SECTION YOU WERE MISSING) --}}
                <div class="card shadow mb-4 border-left-success">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-success">Financial Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Opening Balance (Initial Cash Position)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-success text-white border-success">
                                        {{ $manager->currency_symbol ?? 'UGX' }}
                                    </span>
                                </div>
                                <input type="number" step="0.01" name="opening_balance" class="form-control form-control-lg font-weight-bold text-success" 
                                       value="{{ old('opening_balance', $manager->opening_balance ?? 0) }}">
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Enter the cash amount you have <strong>before</strong> recording any system transactions. This will update your Dashboard Opening Balance.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- 2. BUSINESS DETAILS --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Business Branding (Receipt Details)</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $manager->company_name) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">Business Phone Number</label>
                                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $manager->phone_number) }}" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">Currency Symbol</label>
                                    <input type="text" class="form-control" value="{{ $manager->currency_symbol }}" readonly>
                                    <small class="text-muted">Managed by Admin</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold">Business Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $manager->address) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Company Logo</label>
                            <input type="file" name="company_logo" class="form-control-file">
                            @if($manager->company_logo_path)
                                <div class="mt-2 p-1 border rounded d-inline-block bg-light">
                                    <img src="{{ asset('storage/' . $manager->company_logo_path) }}" alt="Logo" style="height: 40px;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 3. LOGIN DETAILS --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">Login Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label small">Full Name</label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label small">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-danger small font-weight-bold">Security: Only fill below to change password</h6>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="small">New Password</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="small">Confirm Password</label>
                                    <input type="password" name="password_confirmation" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <button type="submit" class="btn btn-primary btn-lg shadow px-5">
                        <i class="fas fa-save mr-2"></i> Update All Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- RIGHT COLUMN: RECEIPT PREVIEW --}}
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Receipt Preview</h6>
                </div>
                <div class="card-body bg-gray-100 text-center p-4">
                    <div class="receipt-preview border p-3 bg-white shadow-sm mx-auto" style="max-width: 300px; font-family: monospace;">
                        <h4 class="text-uppercase font-weight-bold mb-1" id="previewName">
                            {{ $manager->company_name ?? 'COMPANY NAME' }}
                        </h4>
                        <p class="mb-0 small" id="previewAddress">
                            {{ $manager->address ?? 'Address' }}
                        </p>
                        <p class="mb-0 small" id="previewPhone">
                            Tel: {{ $manager->phone_number ?? '0700...' }}
                        </p>
                        <div style="border-top: 3px double #000; margin: 15px 0;"></div>
                        <p class="font-weight-bold mb-0">*** PAYMENT RECEIPT ***</p>
                        <div style="border-top: 1px dashed #000; margin: 15px 0;"></div>
                        <p class="small text-muted">This is a mockup of how your header will appear on printed receipts.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('input[name="company_name"]').on('input', function() {
            $('#previewName').text($(this).val() || 'COMPANY NAME');
        });
        $('input[name="address"]').on('input', function() {
            $('#previewAddress').text($(this).val() || 'Address');
        });
        $('input[name="phone_number"]').on('input', function() {
            $('#previewPhone').text('Tel: ' + ($(this).val() || '0700...'));
        });
    });
</script>
@endpush

@endsection