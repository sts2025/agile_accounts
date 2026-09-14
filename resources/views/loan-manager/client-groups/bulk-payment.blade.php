<?php $currency = \App\Models\LoanManager::getCurrency(); ?>
@extends('layouts.manager')

@section('title', 'Record Group Payments — ' . $group->name)

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Record Group Payments — {{ $group->name }}</h1>
        <a href="{{ route('client-groups.show', $group->id) }}" class="btn btn-secondary shadow-sm">Back</a>
    </div>

    @if (empty($rows) || count($rows) === 0)
        <div class="alert alert-warning">No outstanding loans in this group to collect against.</div>
    @else
        <form method="POST" action="{{ route('client-groups.bulk-payment.store', $group->id) }}">
            @csrf

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Collect</th>
                                    <th>Client</th>
                                    <th>Loan #</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end">Principal Paid</th>
                                    <th class="text-end pe-3">Interest Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $i => $row)
                                    <tr>
                                        <td class="ps-3">
                                            <input type="hidden" name="payments[{{ $i }}][loan_id]" value="{{ $row->loan->id }}">
                                            <input type="checkbox" class="form-check-input" name="payments[{{ $i }}][include]" value="1">
                                        </td>
                                        <td>{{ $row->client->name ?? 'Unknown' }}</td>
                                        <td>#{{ $row->loan->id }}</td>
                                        <td class="text-end">{{ $currency }} {{ number_format($row->balance, 2) }}</td>
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="payments[{{ $i }}][principal_paid]" value="{{ $row->suggested_principal }}">
                                        </td>
                                        <td class="text-end pe-3">
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="payments[{{ $i }}][interest_paid]" value="{{ $row->suggested_interest }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <p class="text-muted small mt-2">Only rows with the "Collect" box checked and a nonzero amount are recorded. Suggested amounts are based on an even split of the loan's term — adjust as needed before submitting.</p>

            <button type="submit" class="btn btn-primary mt-2">Record Checked Payments</button>
        </form>
    @endif
</div>
@endsection
