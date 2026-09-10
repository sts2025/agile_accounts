<?php $currency = \App\Models\LoanManager::getCurrency(); ?>
@extends('layouts.manager')

@section('title', 'Officer Performance')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-user-tie text-secondary me-2"></i> Officer Performance</h1>
            <p class="text-muted mb-0">Portfolio scorecard per staff member, based on each client's assigned officer. Collections shown are for {{ $month }}.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Officer</th>
                            <th class="text-end">Clients</th>
                            <th class="text-end">Active Loans</th>
                            <th class="text-end">Portfolio Outstanding</th>
                            <th class="text-end">PAR30 (31+ days)</th>
                            <th class="text-end">PAR30 %</th>
                            <th class="text-end pe-3">Collections ({{ $month }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $row['label'] }}</td>
                                <td class="text-end">{{ $row['clients'] }}</td>
                                <td class="text-end">{{ $row['active_loans'] }}</td>
                                <td class="text-end">{{ $currency }} {{ number_format($row['portfolio_outstanding'], 2) }}</td>
                                <td class="text-end {{ $row['par30_outstanding'] > 0 ? 'text-danger' : '' }}">{{ $currency }} {{ number_format($row['par30_outstanding'], 2) }}</td>
                                <td class="text-end">
                                    @if ($row['portfolio_outstanding'] > 0.009)
                                        {{ number_format(($row['par30_outstanding'] / $row['portfolio_outstanding']) * 100, 1) }}%
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="text-end pe-3">{{ $currency }} {{ number_format($row['collections_mtd'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No staff or portfolio data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3">
        "Officer" is whichever staff member a client is assigned to under Client &rarr; Customer Account Settings. Clients with no assigned officer are grouped under "Unassigned".
    </p>
</div>
@endsection
