@extends('layouts.manager')
@section('title', 'All Cash Transfers')
@section('content')
    <div class="card">
        <div class="card-header"><h1>All Cash Transfers (Payables/Receivables)</h1></div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount (UGX)</th></tr></thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($transfer->transaction_date)->format('d M, Y') }}</td>
                            <td>
                                @if($transfer->type == 'in')
                                    <span class="badge bg-success">Receivable</span>
                                @else
                                    <span class="badge bg-danger">Payable</span>
                                @endif
                            </td>
                            <td>{{ $transfer->description }}</td>
                            <td class="text-end">{{ number_format($transfer->amount, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No cash transfers have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection