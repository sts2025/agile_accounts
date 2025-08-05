@extends('layouts.manager')
@section('title', 'All Bank Deposits')
@section('content')
    <div class="card">
        <div class="card-header"><h1>All Bank Deposits</h1></div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>Date</th><th>Reference No.</th><th class="text-end">Amount (UGX)</th></tr></thead>
                <tbody>
                    @forelse ($deposits as $deposit)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($deposit->deposit_date)->format('d M, Y') }}</td>
                            <td>{{ $deposit->reference_number ?? 'N/A' }}</td>
                            <td class="text-end">{{ number_format($deposit->amount, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No bank deposits have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection