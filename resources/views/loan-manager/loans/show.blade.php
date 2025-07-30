@extends('layouts.manager')

@section('title', 'Loan Details')

@section('content')
    {{-- This check prevents an error if the loan object is not found --}}
    @if($loan)
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
                    $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
                    $totalRepayable = $loan->principal_amount + $totalInterest;
                    $remainingBalance = $totalRepayable - $totalPaid;
                    $badgeColor = 'bg-secondary';
                    switch ($loan->status) {
                        case 'active': $badgeColor = 'bg-primary'; break;
                        case 'paid': $badgeColor = 'bg-success'; break;
                        case 'defaulted': $badgeColor = 'bg-danger'; break;
                    }
                @endphp
                <p><strong>Client:</strong> {{ $loan->client->name ?? 'Client Not Found' }}</p>
                <p><strong>Principal Amount:</strong> UGX {{ number_format($loan->principal_amount, 2) }}</p>
                <p><strong>Processing Fee:</strong> UGX {{ number_format($loan->processing_fee, 2) }}</p>
                <p><strong>Total Paid:</strong> <span class="text-success">UGX {{ number_format($totalPaid, 2) }}</span></p>
                <p><strong>Remaining Balance:</strong> <span class="text-danger">UGX {{ number_format($remainingBalance, 2) }}</span></p>
                <p><strong>Status:</strong> <span class="badge {{ $badgeColor }}">{{ ucfirst($loan->status) }}</span></p>
            </div>
        </div>

        {{-- Collateral Card --}}
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
        
        {{-- Guarantors Card --}}
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
        
        {{-- Payment History Card --}}
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
                                <td>
                                    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-secondary btn-sm" target="_blank">Receipt</a>
                                    <a href="{{ route('payments.edit.confirm', $payment->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No payments have been recorded for this loan yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Repayment Schedule Card --}}
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
    @else
        <div class="alert alert-danger">Loan not found.</div>
    @endif
@endsection

@push('modals')
    @if($loan)
        {{-- Only the Payment Modal is needed on this page --}}
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">Record Payment for Loan #{{ $loan->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('payments.store') }}">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                            <div class="mb-3"><label class="form-label">Amount Paid (UGX)</label><input type="number" step="0.01" class="form-control" name="amount_paid" required></div>
                            <div class="mb-3"><label class="form-label">Payment Date</label><input type="date" class="form-control" name="payment_date" value="{{ now()->toDateString() }}" required></div>
                            <div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Mobile Money">Mobile Money</option></select></div>
                            <div class="mb-3"><label class="form-label">Notes (Optional)</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Payment</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endpush