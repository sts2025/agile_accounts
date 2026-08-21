@extends('layouts.manager')

@section('title', 'Preview Dividend Distribution')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-eye text-warning me-2"></i> Preview Dividend Distribution</h1>
        <a href="{{ route('mfi.dividends.create') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Start Over
        </a>
    </div>

    <div class="alert alert-info shadow-sm border-start border-info border-4">
        <strong>Pool:</strong> {{ optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX' }} {{ number_format($poolAmount) }}
        @if($description) &middot; {{ $description }} @endif
        &middot; <strong>{{ rtrim(rtrim(number_format($totalUnits, 4), '0'), '.') }}</strong> total units.
        Nothing has been paid out yet — review below, then confirm.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Proposed Payouts</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Client</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Payout</th>
                            <th class="text-center pe-4">Destination</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $row->client_name }}</td>
                            <td class="text-end font-monospace">{{ rtrim(rtrim(number_format($row->units, 4), '0'), '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-success">{{ number_format($row->payout) }}</td>
                            <td class="text-center pe-4">
                                @if($row->has_savings)
                                    <span class="badge bg-success bg-opacity-10 text-success">Savings account</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning">No savings account — will be skipped</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form action="{{ route('mfi.dividends.distribute') }}" method="POST" onsubmit="return confirm('This will move real money into member savings accounts and cannot be undone. Continue?');">
        @csrf
        <input type="hidden" name="pool_amount" value="{{ $poolAmount }}">
        <input type="hidden" name="description" value="{{ $description }}">
        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
            <i class="fas fa-check-circle me-2"></i> Confirm &amp; Distribute
        </button>
        <a href="{{ route('mfi.dividends.create') }}" class="btn btn-light fw-bold">Cancel</a>
    </form>
</div>
@endsection
