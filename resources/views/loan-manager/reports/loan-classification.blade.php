<?php
$currency = \App\Models\LoanManager::getCurrency();

$tierColors = [
    'Normal' => 'success',
    'Watch' => 'info',
    'Substandard' => 'warning',
    'Doubtful' => 'dark',
    'Loss' => 'danger',
];
?>
@extends('layouts.manager')

@section('title', 'Loan Classification & Provisioning')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">Loan Classification & Provisioning</h1>
            <p class="text-muted mb-0">Portfolio-at-risk aging as of {{ \Carbon\Carbon::now()->format('d-M-Y') }}. Recomputed live from current loan/payment data.</p>
        </div>
        <form method="POST" action="{{ route('reports.loan-classification.run') }}" onsubmit="return confirm('Post an adjusting journal entry to bring the Loan Loss Reserve to today\'s required level?');">
            @csrf
            <button type="submit" class="btn btn-primary no-print">
                <i class="fas fa-sync-alt me-2"></i> Run Provisioning
            </button>
        </form>
    </div>

    @if ($reserveAccountMissing)
        <div class="alert alert-warning m-3 mb-0">
            The Loan Loss Reserve account (code 1150) isn't set up yet. Go to <a href="{{ route('chart-of-accounts.index') }}">Chart of Accounts</a> and click "Seed Standard Accounts" to add it before running provisioning.
        </div>
    @endif

    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="text-muted small">Outstanding Portfolio</div>
                    <div class="h4 mb-0">{{ $currency }} {{ number_format($summary['total_outstanding'], 0) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="text-muted small">Required Provision</div>
                    <div class="h4 mb-0 text-danger">{{ $currency }} {{ number_format($summary['total_provision'], 0) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="text-muted small">Current Reserve Balance</div>
                    <div class="h4 mb-0">{{ $currentReserve === null ? '—' : $currency . ' ' . number_format($currentReserve, 0) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="text-muted small">Loans Classified</div>
                    <div class="h4 mb-0">{{ count($rows) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-4">
            @foreach ($summary['by_tier'] as $label => $tier)
                <div class="col-6" style="flex: 1 1 20%; max-width: 20%;">
                    <div class="border rounded p-2 text-center h-100">
                        <span class="badge bg-{{ $tierColors[$label] ?? 'secondary' }} mb-1">{{ $label }}</span>
                        <div class="fw-bold">{{ $tier['count'] }} loan(s)</div>
                        <div class="small text-muted">{{ $currency }} {{ number_format($tier['outstanding'], 0) }}</div>
                        <div class="small text-danger">Provision: {{ $currency }} {{ number_format($tier['provision'], 0) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th class="text-center">Classification</th>
                        <th class="text-center">Days Late</th>
                        <th class="text-end">Outstanding ({{ $currency }})</th>
                        <th class="text-center">Provision Rate</th>
                        <th class="text-end">Provision ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', $row['loan']->client_id) }}">{{ $row['loan']->client->name ?? 'N/A' }}</a>
                                <div class="small text-muted">Loan #{{ $row['loan']->id }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $tierColors[$row['label']] ?? 'secondary' }}">{{ $row['label'] }}</span>
                            </td>
                            <td class="text-center">{{ $row['days_late'] }}</td>
                            <td class="text-end">{{ number_format($row['outstanding'], 2) }}</td>
                            <td class="text-center">{{ number_format($row['provision_rate'], 2) }}%</td>
                            <td class="text-end text-danger fw-bold">{{ number_format($row['provision_amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle me-2"></i> No outstanding disbursed loans to classify.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($rows) > 0)
                    <tfoot class="table-group-divider">
                        <tr>
                            <th colspan="3" class="text-end">TOTALS:</th>
                            <th class="text-end">{{ number_format($summary['total_outstanding'], 2) }}</th>
                            <th></th>
                            <th class="text-end text-danger">{{ number_format($summary['total_provision'], 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Provisioning Run History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Run Date</th>
                        <th class="text-end">Loans</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end">Required Reserve</th>
                        <th class="text-end">Delta</th>
                        <th>Journal Entry</th>
                        <th>Run By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td>{{ $run->run_date->format('d-M-Y') }}</td>
                            <td class="text-end">{{ $run->loan_count }}</td>
                            <td class="text-end">{{ number_format($run->total_outstanding, 2) }}</td>
                            <td class="text-end">{{ number_format($run->required_reserve, 2) }}</td>
                            <td class="text-end {{ $run->delta > 0 ? 'text-danger' : ($run->delta < 0 ? 'text-success' : '') }}">
                                {{ $run->delta > 0 ? '+' : '' }}{{ number_format($run->delta, 2) }}
                            </td>
                            <td>
                                @if ($run->journal_entry_id)
                                    <a href="{{ route('journal-entries.show', $run->journal_entry_id) }}">#{{ $run->journal_entry_id }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $run->createdBy->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No provisioning runs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
