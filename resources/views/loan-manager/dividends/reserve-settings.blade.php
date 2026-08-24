@extends('layouts.manager')

@section('title', 'Statutory Reserve Policy')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-shield-alt text-primary me-2"></i> Statutory Reserve Policy</h1>
        <a href="{{ route('mfi.reserve.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Statutory Reserve
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('mfi.reserve.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0 mb-4" style="max-width: 600px;">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary">Reserve Percentage</h6>
                <small class="text-muted">The share of net surplus set aside into the Statutory Reserve Fund before any of it is considered available for member dividends. 20% is a common regulatory default — check your own jurisdiction's requirement.</small>
            </div>
            <div class="card-body">
                <label class="form-label fw-bold text-dark">Statutory Reserve %</label>
                <div class="input-group" style="max-width: 200px;">
                    <input type="number" step="0.01" min="0" max="100" name="statutory_reserve_percent" class="form-control shadow-sm" value="{{ $settings->statutory_reserve_percent }}" required>
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary fw-bold px-4">
            <i class="fas fa-save me-2"></i> Save Policy
        </button>
    </form>
</div>
@endsection
