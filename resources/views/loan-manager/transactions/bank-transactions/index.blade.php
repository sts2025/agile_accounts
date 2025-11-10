<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
// NOTE: Assuming $bankTransactions is passed to the view, not $transactions
$bankTransactions = $bankTransactions ?? $transactions ?? collect(); 
$startDate = $startDate ?? \Carbon\Carbon::now()->startOfMonth()->toDateString();
$endDate = $endDate ?? \Carbon\Carbon::now()->endOfMonth()->toDateString();
?>
@extends('layouts.manager')

@section('title', 'Bank Deposits & Withdrawals')

@section('content')
<div class="card" id="printable-area">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h1>Bank Deposits & Withdrawals</h1>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('bank-transactions.index') }}" class="mb-4 p-3 bg-light border rounded no-print">
            <div class="row align-items-end">
                <div class="col-md-4"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ $startDate }}"></div>
                <div class="col-md-4"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ $endDate }}"></div>
                <div class="col-md-4"><button type="submit" class="btn btn-secondary w-100">Filter Report</button></div>
            </div>
        </form>

        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-end">Amount ({{ $currency }})</th> {{-- FIX 1: Dynamic Currency --}}
                </tr>
            </thead>
            <tbody>
                @php
                    $totalDeposits = 0;
                    $totalWithdrawals = 0;
                @endphp
                @forelse($bankTransactions as $transaction)
                    @php
                        if ($transaction->type === 'Deposit') {
                            $totalDeposits += $transaction->amount;
                        } else {
                            $totalWithdrawals += $transaction->amount;
                        }
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->deposit_date)->format('d M, Y') }}</td>
                        <td><span class="badge bg-{{ $transaction->type === 'Deposit' ? 'success' : 'danger' }}">{{ ucfirst($transaction->type) }}</span></td>
                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                        <td class="text-end {{ $transaction->type === 'Deposit' ? 'text-success' : 'text-danger' }}">{{ number_format($transaction->amount, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No bank transactions found for the selected period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-group-divider fw-bold">
                <tr>
                    <td colspan="3" class="text-end">Total Deposits (Cash to Bank)</td>
                    <td class="text-end text-success">{{ $currency }} {{ number_format($totalDeposits, 0) }}</td> {{-- FIX 2 --}}
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Total Withdrawals (Bank to Cash)</td>
                    <td class="text-end text-danger">{{ $currency }} {{ number_format($totalWithdrawals, 0) }}</td> {{-- FIX 3 --}}
                </tr>
                <tr class="table-info">
                    <td colspan="3" class="text-end">Net Bank Movement</td>
                    <td class="text-end">{{ $currency }} {{ number_format($totalDeposits - $totalWithdrawals, 0) }}</td> {{-- FIX 4 --}}
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none; }
    }
</style>
@endsection