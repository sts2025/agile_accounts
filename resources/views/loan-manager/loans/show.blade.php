@extends('layouts.app')

@section('title', 'Loan Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Loan Details</h1>
        <div class="btn-group">
            <a href="{{ route('loans.agreement.pdf', $loan->id) }}" class="btn btn-secondary" target="_blank">Print Agreement</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">Record a Payment</button>
        </div>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-secondary btn-sm mb-3">Back to Loan List</a>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    <hr>

    {{-- Loan Summary Card --}}
    <div class="card mb-4">
        <div class="card-header">Loan Summary</div>
        <div class="card-body">
            @php
                $totalPaid = $loan->payments->sum('amount_paid');
                // This check prevents errors if the schedule is empty
                $monthlyPayment = !empty($schedule) ? $schedule[0]['payment_amount'] : 0;
                $totalLoanCost = $monthlyPayment * $loan->term;
                $remainingBalance = $totalLoanCost > 0 ? $totalLoanCost - $totalPaid : $loan->principal_amount - $totalPaid;
                
                $badgeColor = 'bg-secondary';
                switch ($loan->status) {
                    case 'active': $badgeColor = 'bg-primary'; break;
                    case 'paid': $badgeColor = 'bg-success'; break;
                    case 'defaulted': $badgeColor = 'bg-danger'; break;
                }
            @endphp
            {{-- This check prevents an error if the client was deleted --}}
            <p><strong>Client:</strong> {{ $loan->client->name ?? 'Client Not Found' }}</p>
            <p><strong>Principal Amount:</strong> UGX {{ number_format($loan->principal_amount, 2) }}</p>
            <p><strong>Processing Fee:</strong> UGX {{ number_format($loan->processing_fee, 2) }}</p>
            <p><strong>Total Paid:</strong> <span class="text-success">UGX {{ number_format($totalPaid, 2) }}</span></p>
            <p><strong>Remaining Balance:</strong> <span class="text-danger">UGX {{ number_format($remainingBalance, 2) }}</span></p>
            <p><strong>Status:</strong> <span class="badge {{ $badgeColor }}">{{ ucfirst($loan->status) }}</span></p>
        </div>
    </div>

    {{-- The rest of your cards (Collateral, Guarantors, etc.) are below --}}
    {{-- I have added similar safe-checks to them --}}

    <div class="card mb-4">
        <div class="card-header">Collateral</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Type</th><th>Description</th><th>Valuation (UGX)</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($loan->collaterals as $collateral)
                        <tr>
                            <td>{{ $collateral->collateral_type ?? 'N/A' }}</td>
                            <td>{{ $collateral->description ?? 'N/A' }}</td>
                            <td>{{ number_format($collateral->valuation_amount ?? 0, 2) }}</td>
                            <td>@if($collateral->is_released)<span class="badge bg-success">Released</span>@else<span class="badge bg-info">Held</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No collateral was provided for this loan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">Guarantor(s)</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Name</th><th>Phone</th><th>Address</th><th>Occupation</th><th>Relationship</th></tr></thead>
                <tbody>
                    @forelse ($loan->guarantors as $guarantor)
                        <tr>
                            <td>{{ $guarantor->first_name ?? '' }} {{ $guarantor->last_name ?? '' }}</td>
                            <td>{{ $guarantor->phone_number ?? 'N/A' }}</td>
                            <td>{{ $guarantor->address ?? 'N/A' }}</td>
                            <td>{{ $guarantor->occupation ?? 'N/A' }}</td>
                            <td>{{ $guarantor->relationship_to_borrower ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No guarantors were provided for this loan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">Payment History</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Date</th><th>Amount (UGX)</th><th>Method</th><th>Receipt #</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($loan->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ number_format($payment->amount_paid ?? 0, 2) }}</td>
                            <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                            <td>{{ $payment->receipt_number ?? 'N/A' }}</td>
                            <td><a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-secondary btn-sm" target="_blank">Receipt</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No payments have been recorded for this loan yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Repayment Schedule</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>#</th><th>Due Date</th><th>Payment Amount</th><th>Principal</th><th>Interest</th><th>Balance</th></tr></thead>
                <tbody>
                    @if(!empty($schedule))
                        @foreach ($schedule as $payment_schedule)
                            <tr>
                                <td>{{ $payment_schedule['period'] ?? '' }}</td>
                                <td>{{ isset($payment_schedule['due_date']) ? \Carbon\Carbon::parse($payment_schedule['due_date'])->format('F d, Y') : '' }}</td>
                                <td>{{ number_format($payment_schedule['payment_amount'] ?? 0, 2) }}</td>
                                <td>{{ number_format($payment_schedule['principal'] ?? 0, 2) }}</td>
                                <td>{{ number_format($payment_schedule['interest'] ?? 0, 2) }}</td>
                                <td>{{ number_format($payment_schedule['balance'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr><td colspan="6" class="text-center">Repayment schedule could not be calculated.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('modals')
    {{-- Only the Payment Modal is now needed on this page --}}
    <div class="modal fade" id="paymentModal" ...> ... </div>
@endpush