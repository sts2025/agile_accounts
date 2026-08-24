<?php $currency = \App\Models\LoanManager::getCurrency(); ?>
@extends('layouts.manager')

@section('title', 'Statutory Reserve')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-shield-alt text-primary me-2"></i> Statutory Reserve</h1>
        <a href="{{ route('mfi.reserve.settings.edit') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-cog me-2"></i> Reserve Policy
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

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Choose Period</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('mfi.reserve.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-dark">Period Start</label>
                    <input type="date" name="period_start" class="form-control" value="{{ $periodStart }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-dark">Period End</label>
                    <input type="date" name="period_end" class="form-control" value="{{ $periodEnd }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-sync-alt me-2"></i> Recalculate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 text-center h-100 bg-white shadow-sm">
                <div class="text-muted small">Net Surplus (period)</div>
                <div class="h4 mb-0">{{ $currency }} {{ number_format($netSurplus, 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 text-center h-100 bg-white shadow-sm">
                <div class="text-muted small">Required Reserve ({{ number_format($reservePercent, 2) }}%)</div>
                <div class="h4 mb-0 text-primary">{{ $currency }} {{ number_format($requiredReserve, 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 text-center h-100 bg-white shadow-sm">
                <div class="text-muted small">Already Transferred (period)</div>
                <div class="h4 mb-0">{{ $currency }} {{ number_format($alreadyTransferred, 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 text-center h-100 bg-white shadow-sm">
                <div class="text-muted small">Current Reserve Fund Balance</div>
                <div class="h4 mb-0">{{ $currentReserveBalance === null ? 'N/A' : $currency . ' ' . number_format($currentReserveBalance, 0) }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="fw-bold">Outstanding for this period: {{ $currency }} {{ number_format($outstandingForPeriod, 2) }}</div>
                <div class="small text-muted">Transferring posts Dr Retained Earnings / Cr Statutory Reserve Fund for the outstanding amount only — safe to run more than once per period.</div>
            </div>
            <form method="POST" action="{{ route('mfi.reserve.transfer') }}" onsubmit="return confirm('Post the statutory reserve transfer for this period?');">
                @csrf
                <input type="hidden" name="period_start" value="{{ $periodStart }}">
                <input type="hidden" name="period_end" value="{{ $periodEnd }}">
                <button type="submit" class="btn btn-primary fw-bold px-4" {{ $outstandingForPeriod <= 0.01 ? 'disabled' : '' }}>
                    <i class="fas fa-arrow-right me-2"></i> Transfer to Reserve
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Transfer History</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Period</th>
                            <th class="text-end">Net Surplus</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Amount</th>
                            <th>Journal Entry</th>
                            <th class="pe-3">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="ps-3">{{ $transfer->period_start->format('d-M-Y') }} — {{ $transfer->period_end->format('d-M-Y') }}</td>
                                <td class="text-end">{{ number_format($transfer->net_surplus, 2) }}</td>
                                <td class="text-end">{{ number_format($transfer->reserve_percent, 2) }}%</td>
                                <td class="text-end fw-bold">{{ number_format($transfer->reserve_amount, 2) }}</td>
                                <td>
                                    @if ($transfer->journal_entry_id)
                                        <a href="{{ route('journal-entries.show', $transfer->journal_entry_id) }}">#{{ $transfer->journal_entry_id }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="pe-3">{{ $transfer->createdBy->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No transfers recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
