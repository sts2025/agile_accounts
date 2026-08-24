@extends('layouts.manager')

@section('title', 'Declare Dividend')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-hand-holding-usd text-warning me-2"></i> Declare Dividend</h1>
        <a href="{{ route('mfi.shares.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Share Accounts
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="alert alert-info shadow-sm border-start border-info border-4">
        <strong>This year so far:</strong> Net surplus {{ number_format($netSurplus) }}
        &middot; Statutory reserve required ({{ number_format($reservePercent, 2) }}%): {{ number_format($requiredReserve) }}
        &middot; Estimated distributable after reserve: <strong>{{ number_format($estimatedDistributable) }}</strong>.
        This is advisory only — it won't stop you from entering a different pool amount.
        <a href="{{ route('mfi.reserve.index') }}">Manage the statutory reserve transfer here.</a>
    </div>

    @if($totalUnits <= 0)
        <div class="alert alert-warning shadow-sm border-start border-warning border-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No shareholders hold any units yet — there's nothing to distribute a dividend against.
        </div>
    @else
    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-primary">Dividend Pool</h6>
        </div>
        <div class="card-body p-4 bg-light">
            <p class="text-muted small">
                Total units currently in issue: <strong>{{ rtrim(rtrim(number_format($totalUnits, 4), '0'), '.') }}</strong>.
                The pool you enter will be split proportionally by units held, and credited straight into each
                member's savings account. Members with no active savings account will be skipped so you can pay them manually.
            </p>

            <form action="{{ route('mfi.dividends.preview') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Total Dividend Pool ({{ optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX' }})</label>
                    <input type="number" step="0.01" min="1" name="pool_amount" class="form-control form-control-lg" required value="{{ old('pool_amount') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description (optional)</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. FY2026 Dividend" value="{{ old('description') }}">
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-eye me-2"></i> Preview Distribution
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
