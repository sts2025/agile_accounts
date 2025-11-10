<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')

@section('title', 'Daily Transaction Report')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Daily Transaction Report</h1>
            <p class="text-muted">For date: {{ \Carbon\Carbon::parse($reportDate)->format('F d, Y') }}</p>
        </div>
        <div>
            {{-- *** THE FINAL CORRECTED FIX: Using the defined route name 'reports.daily.pdf' *** --}}
            <a href="{{ route('reports.daily.pdf', ['date' => $reportDate]) }}" class="btn btn-primary" target="_blank">Print Report</a> 
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.daily') }}" class="row g-3 align-items-center">
                <div class="col-auto"><label class="form-label">Select Date</label><input type="date" class="form-control" name="date" value="{{ $reportDate }}"></div>
                <div class="col-auto mt-4"><button type="submit" class="btn btn-primary">View Report</button></div>
            </form>
        </div>
    </div>
    <div class="row mb-4">
        {{-- Total Given Out --}}
        <div class="col-md-3"><div class="card text-white bg-danger h-100"><div class="card-body"><h5 class="card-title">Total Given Out</h5><p class="card-text fs-4">{{ $currency }} {{ number_format($summary['total_loaned_principal'], 0) }}</p><small>{{ $summary['count_loans_given'] }} Loans</small></div></div></div>
        {{-- Total Received --}}
        <div class="col-md-3"><div class="card text-white bg-success h-100"><div class="card-body"><h5 class="card-title">Total Received</h5><p class="card-text fs-4">{{ $currency }} {{ number_format($summary['total_payments_received'], 0) }}</p><small>{{ $summary['count_payments_received'] }} Payments</small></div></div></div>
        {{-- Processing Fees --}}
        <div class="col-md-3"><div class="card text-white bg-info h-100"><div class="card-body"><h5 class="card-title">Processing Fees</h5><p class="card-text fs-4">{{ $currency }} {{ number_format($summary['total_processing_fees'], 0) }}</p><small>from {{ $summary['count_loans_given'] }} Loans</small></div></div></div>
        {{-- Net Cash Flow --}}
        <div class="col-md-3"><div class="card text-white bg-dark h-100"><div class="card-body"><h5 class="card-title">Net Cash Flow</h5>@php $netCashFlow = $summary['total_payments_received'] - $summary['total_loaned_principal']; @endphp<p class="card-text fs-4 @if($netCashFlow < 0) text-warning @endif">{{ $currency }} {{ number_format($netCashFlow, 0) }}</p><small>Received minus Given</small></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h4>Loans Given Out Details</h4></div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Client Name</th><th class="text-end">Principal Amount ({{ $currency }})</th><th class="text-end">Processing Fee ({{ $currency }})</th></tr></thead>
                <tbody>
                    @forelse ($loansGiven as $loan)
                        <tr><td>{{ $loan->client->name }}</td><td class="text-end">{{ number_format($loan->principal_amount, 0) }}</td><td class="text-end">{{ number_format($loan->processing_fee, 0) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No loans were given out on this date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h4>Payments Received Details</h4></div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Client Name</th><th>Receipt #</th><th class="text-end">Amount Paid ({{ $currency }})</th></tr></thead>
                <tbody>
                    @forelse ($paymentsReceived as $payment)
                        <tr><td>{{ $payment->loan->client->name }}</td><td>{{ $payment->receipt_number }}</td><td class="text-end">{{ number_format($payment->amount_paid, 0) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No payments were received on this date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection