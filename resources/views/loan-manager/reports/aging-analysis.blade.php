@extends('layouts.manager')
@section('title', 'Loan Aging Analysis')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>My Loan Aging Analysis</h1>
            <p class="text-muted">Report generated on {{ now()->format('F d, Y') }}</p>
        </div>
        <div>
            <a href="{{ route('manager.reports.aging-analysis.pdf') }}" class="btn btn-primary" target="_blank">Download PDF</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Client's Name</th>
                            <th>Guarantor</th>
                            <th>Date Given</th>
                            <th>Final Due Date</th>
                            <th class="text-end">Amount Given</th>
                            <th class="text-end">Interest</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Total Arrears</th>
                            <th class="text-end">Days Missed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($analyzedLoans as $index => $item)
                            @php
                                $loan = $item->loan;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $loan->client->name ?? 'N/A' }} <br>
                                    <small class="text-muted">{{ $loan->client->phone_number ?? '' }}</small>
                                </td>
                                <td>
                                    {{ $loan->guarantors->first()->full_name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $loan->guarantors->first()->phone_number ?? '' }}</small>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($loan->start_date)->format('d-M-Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($loan->end_date)->format('d-M-Y') }}</td>
                                <td class="text-end">{{ number_format($loan->principal_amount, 0) }}</td>
                                <td class="text-end">{{ number_format($loan->total_interest, 0) }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->balance, 0) }}</td>
                                <td class="text-end {{ $item->total_arrears > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($item->total_arrears, 0) }}</td>
                                <td class="text-end {{ $item->days_missed > 0 ? 'text-danger fw-bold' : '' }}">{{ $item->days_missed }} DAYS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">There are no active loans to analyze.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection