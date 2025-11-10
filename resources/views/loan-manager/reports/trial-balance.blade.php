<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')
@section('title', 'Trial Balance')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h1>Trial Balance</h1>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button> {{-- Added no-print class --}}
    </div>
    <div class="card-body">
        <p class="text-muted">As of: {{ \Carbon\Carbon::now()->format('F d, Y H:i:s') }}</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60%;">Account Name</th>
                        <th class="text-end" style="width: 20%;">Debit ({{ $currency }})</th> {{-- FIX 1: Dynamic Currency --}}
                        <th class="text-end" style="width: 20%;">Credit ({{ $currency }})</th> {{-- FIX 2: Dynamic Currency --}}
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp

                    @forelse($accounts as $account)
                    @php
                        $debit = $account->debit_balance ?? 0;
                        $credit = $account->credit_balance ?? 0;
                        
                        $totalDebit += $debit;
                        $totalCredit += $credit;
                    @endphp
                    <tr>
                        <td>{{ $account->name }}</td>
                        <td class="text-end">{{ number_format($debit, 2) }}</td>
                        <td class="text-end">{{ number_format($credit, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">No accounts found to generate Trial Balance.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-dark fw-bold">
                    <tr>
                        <td>TOTALS</td>
                        <td class="text-end">{{ $currency }} {{ number_format($totalDebit, 2) }}</td> {{-- FIX 3: Dynamic Currency --}}
                        <td class="text-end">{{ $currency }} {{ number_format($totalCredit, 2) }}</td> {{-- FIX 4: Dynamic Currency --}}
                    </tr>
                    @if (abs($totalDebit - $totalCredit) > 0.01) {{-- Check for near-zero difference --}}
                    <tr class="table-danger">
                        <td colspan="3" class="text-center">
                            WARNING: Debits and Credits do not balance! Difference: {{ $currency }} {{ number_format(abs($totalDebit - $totalCredit), 2) }}
                        </td> {{-- FIX 5: Dynamic Currency --}}
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>
</div>
<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>
@endsection