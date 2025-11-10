<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')

@section('title', 'Loan Aging Report')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">Loan Aging Analysis Statement</h1>
            <p class="text-muted mb-0">Analysis of overdue loans and arrears as of {{ \Carbon\Carbon::now()->format('d-M-Y') }}.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="fas fa-print me-2"></i> Print Report
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Client Name</th>
                        <th style="width: 15%;">Guarantor</th>
                        <th style="width: 10%;">Date Given</th>
                        <th style="width: 10%;">Next Due Date</th>
                        <th class="text-end" style="width: 12%;">Principal ({{ $currency }})</th>
                        <th class="text-end" style="width: 10%;">Interest ({{ $currency }})</th>
                        <th class="text-end" style="width: 10%;">Total Arrears ({{ $currency }})</th>
                        <th class="text-center" style="width: 8%;">Days Missed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        {{-- This loop requires overdue loans to have data. --}}
                        @php
                            $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
                            $nextDueDate = $loan->repaymentSchedules->where('status', 'pending')->sortBy('due_date')->first()->due_date ?? 'N/A';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', $loan->client_id) }}">{{ $loan->client->name ?? 'N/A' }}</a>
                            </td>
                            <td>
                                {{ $loan->guarantors->first()->first_name ?? 'N/A' }} {{ $loan->guarantors->first()->last_name ?? '' }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($loan->start_date)->format('d-M-Y') }}</td>
                            <td>{{ $nextDueDate !== 'N/A' ? \Carbon\Carbon::parse($nextDueDate)->format('d-M-Y') : 'N/A' }}</td>
                            <td class="text-end">{{ number_format($loan->principal_amount, 0) }}</td>
                            <td class="text-end">{{ number_format($totalInterest, 0) }}</td>
                            <td class="text-end text-danger fw-bold">
                                {{ number_format($loan->arrears, 0) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger">{{ $loan->days_missed }} Days</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- This message is displayed because no loans meet the overdue criteria in the controller --}}
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle me-2"></i> No overdue loans found. All active loans are up to date.
                                <br>
                                **(If you expect to see data, please ensure you have created a payment schedule and missed at least one payment.)**
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($loans) > 0)
                <tfoot class="table-group-divider">
                    <tr>
                        <th colspan="6" class="text-end">TOTAL OUTSTANDING ARREARS:</th>
                        <th class="text-end text-danger">{{ $currency }} {{ number_format($loans->sum('arrears'), 0) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection