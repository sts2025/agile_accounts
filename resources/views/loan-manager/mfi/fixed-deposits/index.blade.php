@extends('layouts.manager')

@section('title', 'Fixed Deposits')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-lock text-info me-2"></i> Fixed Deposits</h1>
        <a href="{{ route('mfi.fixed-deposits.create') }}" class="btn btn-success fw-bold shadow-sm">
            <i class="fas fa-plus me-2"></i> Open Fixed Deposit
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">All Fixed Deposits</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Account Number</th>
                            <th>Client Name</th>
                            <th class="text-end">Principal</th>
                            <th class="text-center">Maturity Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        @php
                            $matured = $account->maturity_date && \Carbon\Carbon::parse($account->maturity_date)->isPast();
                        @endphp
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary">
                                {{ $account->account_number }}
                                @if($account->nickname)
                                    <br><span class="text-muted small fw-normal font-monospace">{{ $account->nickname }}</span>
                                @endif
                            </td>
                            <td class="fw-bold text-dark">{{ $account->client->name ?? 'Unknown Client' }}</td>
                            <td class="text-end font-monospace">{{ number_format($account->principal_amount) }}</td>
                            <td class="text-center small">
                                {{ $account->maturity_date ? \Carbon\Carbon::parse($account->maturity_date)->format('d M, Y') : 'N/A' }}
                            </td>
                            <td class="text-center">
                                @if($account->status == 'closed')
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Closed</span>
                                @elseif($matured)
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Matured</span>
                                @else
                                    <span class="badge bg-info px-3 py-1 rounded-pill">Active</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('mfi.fixed-deposits.show', $account->id) }}" class="btn btn-sm btn-outline-primary shadow-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-lock fa-3x mb-3 text-light"></i>
                                <h5>No fixed deposits opened yet.</h5>
                                <a href="{{ route('mfi.fixed-deposits.create') }}" class="btn btn-sm btn-outline-primary mt-2">Open First Deposit</a>
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
