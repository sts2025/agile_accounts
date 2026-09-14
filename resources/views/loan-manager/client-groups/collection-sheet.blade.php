<?php $currency = \App\Models\LoanManager::getCurrency(); ?>
@extends('layouts.manager')

@section('title', 'Collection Sheet — ' . $group->name)

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h1 class="h3 mb-0">Collection Sheet — {{ $group->name }}</h1>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm"><i class="fas fa-print me-2"></i> Print</button>
            <a href="{{ route('client-groups.show', $group->id) }}" class="btn btn-secondary shadow-sm">Back</a>
        </div>
    </div>

    <div class="text-center mb-4 d-none d-print-block">
        <h3 class="mb-0">{{ $group->name }} — Collection Sheet</h3>
        <p class="text-muted">Meeting date: ______________________</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Loan #</th>
                            <th class="text-end">Balance</th>
                            <th>Next Due</th>
                            <th class="text-end">Next Due Amount</th>
                            <th class="text-end">Arrears</th>
                            <th class="text-end">Amount Collected</th>
                            <th>Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->client->name ?? 'Unknown' }}</td>
                                <td>#{{ $row->loan->id }}</td>
                                <td class="text-end">{{ $currency }} {{ number_format($row->balance, 2) }}</td>
                                <td>{{ $row->next_due_date?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">{{ $row->next_due_amount !== null ? $currency . ' ' . number_format($row->next_due_amount, 2) : '—' }}</td>
                                <td class="text-end {{ $row->arrears > 0 ? 'text-danger fw-bold' : '' }}">{{ $currency }} {{ number_format($row->arrears, 2) }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No outstanding loans in this group.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 no-print">
        Print this sheet to collect against in the field, then use <a href="{{ route('client-groups.bulk-payment', $group->id) }}">Record Group Payments</a> to enter what was actually collected.
    </p>
</div>

<style>
    @media print {
        .no-print, .sidebar, .main-header, .app-footer { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; }
    }
</style>
@endsection
