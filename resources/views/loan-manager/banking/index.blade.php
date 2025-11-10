<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')

@section('title', 'Bank Transactions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Bank Deposits & Withdrawals</h1>
    <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
</div>

{{-- Filter Form (Assuming $startDate and $endDate are passed from controller) --}}
<form method="GET" action="{{ route('bank-transactions.index') }}" class="mb-4 p-3 bg-light border rounded no-print">
    <div class="row align-items-end">
        <div class="col-md-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary w-100">Filter Report</button>
        </div>
    </div>
</form>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount ({{ $currency }})</th> {{-- FIX --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}</td>
                        <td><span class="badge bg-{{ $transaction->type === 'Deposit' ? 'success' : 'danger' }}">{{ $transaction->type }}</span></td>
                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                        <td class="text-end">{{ number_format($transaction->amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No bank transactions found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
                {{-- Summary Totals Row --}}
                @if(isset($summary))
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">Total Deposits</td>
                        <td class="text-end text-success">{{ $currency }} {{ number_format($summary['total_deposits'] ?? 0, 0) }}</td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">Total Withdrawals</td>
                        <td class="text-end text-danger">{{ $currency }} {{ number_format($summary['total_withdrawals'] ?? 0, 0) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection