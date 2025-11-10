@extends('layouts.manager')
@section('title', 'Client Ledger for ' . $client->name)
@section('content')
<div class="card" id="printable-area">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Client Ledger: {{ $client->name }}</h1>
            <p class="mb-0">Phone: {{ $client->phone_number }}</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Ledger</button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr><th>Date</th><th>Description</th><th class="text-end">Debit (Loan)</th><th class="text-end">Credit (Paid)</th><th class="text-end">Balance</th></tr>
            </thead>
            <tbody>
                @php $balance = 0; @endphp
                @forelse($transactions as $transaction)
                    @php $balance += $transaction->debit - $transaction->credit; @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td class="text-end">{{ $transaction->debit > 0 ? number_format($transaction->debit, 0) : '-' }}</td>
                        <td class="text-end">{{ $transaction->credit > 0 ? number_format($transaction->credit, 0) : '-' }}</td>
                        <td class="text-end fw-bold">{{ number_format($balance, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No transactions found for this client.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr><td colspan="4" class="text-end fw-bold fs-5">Final Balance</td><td class="text-end fw-bold fs-5">{{ number_format($balance, 0) }}</td></tr>
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