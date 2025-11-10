<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')
@section('title', 'Master Transaction List (General Ledger)')

@section('content')
<div class="container-fluid mt-4" id="printable-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Master Transaction List</h1>
            <h5>(General Ledger)</h5>
            <p>A complete list of every single transaction across all modules, sorted by date.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>

    {{-- Date Filter Form --}}
    <form method="GET" action="{{ route('reports.general-ledger') }}" class="mb-4 p-3 bg-light border rounded no-print">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary w-100">Filter Report</button>
            </div>
        </div>
    </form>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Description / Source</th>
                        <th class="text-end">Cash In ({{ $currency }})</th> {{-- FIX 1: Dynamic Currency --}}
                        <th class="text-end">Cash Out ({{ $currency }})</th> {{-- FIX 2: Dynamic Currency --}}
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalIn = 0;
                        $totalOut = 0;
                        $runningBalance = 0; // Added running balance for context
                    @endphp
                    @forelse($transactions as $tx)
                        @php
                            $totalIn += $tx->amount_in;
                            $totalOut += $tx->amount_out;
                            $runningBalance += $tx->amount_in - $tx->amount_out; // Calculate running balance
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tx->date)->format('d M, Y') }}</td>
                            <td>{{ $tx->description }}</td>
                            <td class="text-end text-success">
                                {{ $tx->amount_in > 0 ? number_format($tx->amount_in, 0) : '-' }}
                            </td>
                            <td class="text-end text-danger">
                                {{ $tx->amount_out > 0 ? number_format($tx->amount_out, 0) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No transactions found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">Total Movement</td>
                        <td class="text-end text-success">{{ $currency }} {{ number_format($totalIn, 0) }}</td> {{-- FIX 3: Dynamic Currency --}}
                        <td class="text-end text-danger">{{ $currency }} {{ number_format($totalOut, 0) }}</td> {{-- FIX 4: Dynamic Currency --}}
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end h5">Net Cash Movement (In - Out)</td>
                        <td colspan="2" class="text-end h5 {{ ($totalIn - $totalOut) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency }} {{ number_format($totalIn - $totalOut, 0) }} {{-- FIX 5: Dynamic Currency --}}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
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