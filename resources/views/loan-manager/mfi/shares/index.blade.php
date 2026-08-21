@extends('layouts.manager')

@section('title', 'Share Accounts')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-chart-pie text-warning me-2"></i> Share Accounts</h1>
            <p class="text-muted mb-0 small">Total units in issue: <strong>{{ rtrim(rtrim(number_format($totalUnits, 4), '0'), '.') }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('mfi.dividends.create') }}" class="btn btn-outline-primary fw-bold shadow-sm">
                <i class="fas fa-hand-holding-usd me-2"></i> Declare Dividend
            </a>
            <a href="{{ route('mfi.shares.create') }}" class="btn btn-success fw-bold shadow-sm">
                <i class="fas fa-plus me-2"></i> Open Share Account
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Members with Shares</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Account Number</th>
                            <th>Client Name</th>
                            <th class="text-end">Units Held</th>
                            <th class="text-end">Current Value</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary">
                                {{ $account->account_number }}
                                @if($account->nickname)
                                    <br><span class="text-muted small fw-normal font-monospace">{{ $account->nickname }}</span>
                                @endif
                            </td>
                            <td class="fw-bold text-dark">{{ $account->client->name ?? 'Unknown Client' }}</td>
                            <td class="text-end font-monospace">{{ rtrim(rtrim(number_format($account->units, 4), '0'), '.') }}</td>
                            <td class="text-end text-success fw-bold font-monospace">{{ number_format($account->balance) }}</td>
                            <td class="text-center">
                                @if($account->status == 'active')
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">{{ ucfirst($account->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('mfi.shares.show', $account->id) }}" class="btn btn-sm btn-outline-primary shadow-sm">
                                    <i class="fas fa-exchange-alt"></i> Manage
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-chart-pie fa-3x mb-3 text-light"></i>
                                <h5>No share accounts opened yet.</h5>
                                <a href="{{ route('mfi.shares.create') }}" class="btn btn-sm btn-outline-primary mt-2">Open First Account</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
