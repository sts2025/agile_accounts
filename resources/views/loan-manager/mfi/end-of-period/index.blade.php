@extends('layouts.manager')

@section('title', 'End of Period')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-calendar-check text-primary me-2"></i> End of Period — Savings Interest</h1>
        <a href="{{ route('mfi.savings.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Savings
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-primary">Post Savings Interest</h6>
        </div>
        <div class="card-body p-4 bg-light">
            <p class="text-muted small">
                Calculates and credits interest owed to every active savings account since it was last posted,
                using each account's Product Settings interest rate (simple interest, actual days / 365).
                Accounts on a product with no interest rate configured are skipped. Fixed deposits are not
                affected here — their interest is settled in full when the deposit is closed.
            </p>

            <form action="{{ route('mfi.end-of-period.preview') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Post Interest As Of</label>
                    <input type="date" name="as_of_date" class="form-control" required
                           max="{{ date('Y-m-d') }}" value="{{ old('as_of_date', date('Y-m-d')) }}">
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-eye me-2"></i> Preview Interest Posting
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
