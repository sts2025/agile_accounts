@extends('layouts.manager')

@section('title', 'Open Savings Account')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Open Savings Account</h1>
        <a href="{{ route('mfi.savings.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Accounts
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-plus-circle me-2"></i>New Account Details</h6>
        </div>
        <div class="card-body p-4 bg-light">

            {{-- VISIBLE DATABASE ERRORS --}}
            @if(session('error'))
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>System Error:</strong><br>
                    {{ session('error') }}
                </div>
            @endif

            {{-- VISIBLE VALIDATION ERRORS --}}
            @if($errors->any())
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('mfi.savings.store') }}" method="POST">
                @csrf

                {{-- Client Selection --}}
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Select Client</label>
                    <select name="client_id" id="clientSelect" class="form-select shadow-sm" style="width: 100%;" required>
                        <option value="" disabled selected>Search for a registered client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->phone_number }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i> Client must be registered in the system first.
                    </small>
                </div>

                {{-- Account Type (Standard) --}}
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Account Type</label>
                    <input type="text" class="form-control bg-white shadow-sm fw-bold text-success" value="Standard Savings Account" readonly>
                </div>

                {{-- Opening Deposit --}}
                <div class="card border-success border-2 shadow-sm mb-4">
                    <div class="card-body">
                        <label class="form-label fw-bold text-success text-uppercase mb-2">Initial Cash Deposit (Opening Balance)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-success text-white border-success font-weight-bold">
                                {{ Auth::user()->getCompany()->currency_symbol ?? 'UGX' }}
                            </span>
                            <input type="number" step="any" name="opening_balance" class="form-control text-end fw-bold font-monospace fs-4 text-dark border-success" value="0" min="0" required>
                        </div>
                        <small class="text-muted mt-2 d-block">How much cash is the client depositing right now to open the account?</small>
                    </div>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Open Account & Record Deposit
                    </button>
                    <a href="{{ route('mfi.savings.index') }}" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#clientSelect').select2({
            placeholder: "Search for a client...",
            allowClear: true
        });
    });
</script>
@endpush