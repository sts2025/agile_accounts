<?php
// Fetch dynamic currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
// Define default dates if not passed from controller
$startDate = $startDate ?? \Carbon\Carbon::now()->startOfMonth()->toDateString();
$endDate = $endDate ?? \Carbon\Carbon::now()->endOfMonth()->toDateString();
?>
@extends('layouts.manager')

@section('title', 'Payables & Receivables')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Payables & Receivables</h1>
    <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
</div>

{{-- Filter Form --}}
<form method="GET" action="{{ route('cash-transactions.index') }}" class="mb-4 p-3 bg-light border rounded no-print">
    <div class="row align-items-end">
        <div class="col-md-4">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-md-4">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-secondary w-100">Filter Report</button>
        </div>
    </div>
</form>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr class="table-light">
                        <th style="width: 15%;">Date</th>
                        <th style="width: 15%;">Type</th>
                        <th style="width: 50%;">Description</th>
                        <th class="text-end" style="width: 20%;">Amount ({{ $currency }})</th> {{-- FIX 1: Dynamic Currency --}}
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalPayables = 0;
                        $totalReceivables = 0;
                    @endphp
                    @forelse($transactions as $transaction)
                        @php
                            // FIX 2: Robustly determine the type string and calculate totals
                            $typeCode = strtolower($transaction->type);
                            if ($typeCode === 'p' || $typeCode === 'payable') {
                                $typeDisplay = 'Payable (Out)';
                                $badgeClass = 'danger';
                                $totalPayables += $transaction->amount;
                            } elseif ($typeCode === 'r' || $typeCode === 'receivable') {
                                $typeDisplay = 'Receivable (In)';
                                $badgeClass = 'success';
                                $totalReceivables += $transaction->amount;
                            } else {
                                $typeDisplay = 'Unknown';
                                $badgeClass = 'secondary';
                            }
                        @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}</td>
                        {{-- FIX 3: Displaying Readable Type using $typeDisplay --}}
                        <td><span class="badge bg-{{ $badgeClass }}">{{ $typeDisplay }}</span></td> 
                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                        <td class="text-end">{{ number_format($transaction->amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No payables or receivables found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="table-danger fw-bold">
                        <td colspan="3" class="text-end">Total Payables (Cash Out)</td>
                        <td class="text-end">{{ $currency }} {{ number_format($totalPayables, 0) }}</td> {{-- FIX 4: Dynamic Currency --}}
                    </tr>
                    <tr class="table-success fw-bold">
                        <td colspan="3" class="text-end">Total Receivables (Cash In)</td>
                        <td class="text-end">{{ $currency }} {{ number_format($totalReceivables, 0) }}</td> {{-- FIX 5: Dynamic Currency --}}
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection