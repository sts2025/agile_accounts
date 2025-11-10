@extends('layouts.manager')
@section('title', 'Client Ledger for ' . $client->name)
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h1>Client Ledger: {{ $client->name }}</h1>
                <p class="text-muted mb-0">Phone: {{ $client->phone_number }}</p>
            </div>
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">Back to Clients List</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="text-end">Debit (Loan)</th>
                            <th class="text-end">Credit (Payment)</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $balance = 0;
                        @endphp
                        @forelse($transactions as $transaction)
                            @php
                                $balance += $transaction->debit - $transaction->credit;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td class="text-end">{{ $transaction->debit > 0 ? number_format($transaction->debit, 0) : '' }}</td>
                                <td class="text-end">{{ $transaction->credit > 0 ? number_format($transaction->credit, 0) : '' }}</td>
                                <td class="text-end fw-bold">{{ number_format($balance, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No transactions have been recorded for this client.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Final Balance</td>
                            <td class="text-end fw-bold">{{ number_format($balance, 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection