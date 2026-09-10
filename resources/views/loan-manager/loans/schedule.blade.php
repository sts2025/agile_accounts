<?php $currency = \App\Models\LoanManager::getCurrency(); ?>
@extends('layouts.manager')

@section('title', 'Repayment Schedule')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">Repayment Schedule — Loan #{{ $loan->reference_id ?? str_pad($loan->id, 4, '0', STR_PAD_LEFT) }}</h1>
            <p class="mb-0 text-muted">Client: <strong>{{ $loan->client->name }}</strong> &middot; {{ $loan->term }} {{ $loan->repayment_frequency }} installments from {{ \Carbon\Carbon::parse($loan->start_date)->format('d M Y') }}</p>
        </div>
        <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left"></i> Back to Loan
        </a>
    </div>

    @if (empty($rows))
        <div class="alert alert-warning">
            No schedule has been generated for this loan yet. This can happen for loans disbursed before this feature existed — reschedule the loan (even with the same terms) to generate one.
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0"><div class="card-body">
                    <div class="text-muted small">Total Scheduled</div>
                    <div class="h5 mb-0">{{ $currency }} {{ number_format($totalScheduled, 2) }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0"><div class="card-body">
                    <div class="text-muted small">Total Paid</div>
                    <div class="h5 mb-0">{{ $currency }} {{ number_format($totalPaid, 2) }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0"><div class="card-body">
                    <div class="text-muted small">Remaining</div>
                    <div class="h5 mb-0">{{ $currency }} {{ number_format(max(0, $totalScheduled - $totalPaid), 2) }}</div>
                </div></div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Due Date</th>
                                <th class="text-end">Installment Amount</th>
                                <th class="text-end">Cumulative Due</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $badge = match($row['status']) {
                                        'Paid' => 'success',
                                        'Partially Paid' => 'info',
                                        'Overdue' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3">{{ $row['installment']->installment_number }}</td>
                                    <td>{{ $row['installment']->due_date->format('d M Y') }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format($row['installment']->amount, 2) }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format($row['cumulative_due'], 2) }}</td>
                                    <td class="pe-3"><span class="badge bg-{{ $badge }}">{{ $row['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-3">
            Status is computed from total payments received against the cumulative amount due by each installment — it isn't tracking which specific installment a payment was "for", since repayments aren't earmarked to individual installments.
        </p>
    @endif
</div>
@endsection
