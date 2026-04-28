@extends('layouts.manager')

@section('title', 'Savings Accounts')

@section('content')
<div class="container-fluid px-0">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-piggy-bank text-warning me-2"></i> Savings Accounts</h1>
        <a href="{{ route('mfi.savings.create') }}" class="btn btn-success fw-bold shadow-sm">
            <i class="fas fa-plus me-2"></i> Open New Account
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Accounts Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Active Deposits & Savings</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Account Number</th>
                            <th>Client Name</th>
                            <th>Contact</th>
                            <th class="text-end">Current Balance</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary">{{ $account->account_number }}</td>
                            <td class="fw-bold text-dark">{{ $account->client->name ?? 'Unknown Client' }}</td>
                            <td class="text-muted small">{{ $account->client->phone_number ?? 'N/A' }}</td>
                            <td class="text-end text-success fw-bold font-monospace bg-light fs-5">
                                {{ number_format($account->balance) }}
                            </td>
                            <td class="text-center">
                                @if($account->status == 'active')
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">{{ ucfirst($account->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
    <a href="{{ route('mfi.savings.show', $account->id) }}" class="btn btn-sm btn-outline-primary shadow-sm">
        <i class="fas fa-exchange-alt"></i> Transact
    </a>
</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-piggy-bank fa-3x mb-3 text-light"></i>
                                <h5>No savings accounts opened yet.</h5>
                                <a href="{{ route('mfi.savings.create') }}" class="btn btn-sm btn-outline-primary mt-2">Open First Account</a>
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