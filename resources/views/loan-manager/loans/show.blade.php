<?php
// Note: This file assumes $loan is passed from the LoanController::show() method.
// We fetch the currency once at the top for cleaner code.
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')

@section('title', 'Loan Details')

@section('content')
    @if($loan)
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-2 mb-md-0 text-gray-800">Loan Details</h1>
            
            <div class="btn-group" role="group" aria-label="Loan Actions">
                
                {{-- Print Forms Link (using the corrected route name) --}}
                <a href="{{ route('reports.print-forms') }}" class="btn btn-info">
                    <i class="fas fa-print me-1"></i> Print Forms
                </a>
                
                {{-- Record Payment Button --}}
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                    <i class="fas fa-dollar-sign me-1"></i> Record Payment
                </button>

            </div>
        </div>
        
        <a href="{{ route('loans.index') }}" class="btn btn-secondary btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Back to Loan List
        </a>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <hr>

        {{-- Loan Summary Card (CURRENCY FIX APPLIED) --}}
        <div class="card mb-4 shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Loan Summary</h6>
            </div>
            <div class="card-body">
                @php
                    // Note: Ensure $loan->payments->sum('amount') is correct for your database schema
                    $totalPaid = $loan->payments->sum('amount'); 
                    $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
                    $totalRepayable = $loan->principal_amount + $totalInterest + $loan->processing_fee;
                    $remainingBalance = $totalRepayable - $totalPaid;
                    
                    $badgeColor = 'bg-secondary';
                    switch ($loan->status) {
                        case 'active': $badgeColor = 'bg-primary'; break;
                        case 'paid': $badgeColor = 'bg-success'; break;
                        case 'defaulted': $badgeColor = 'bg-danger'; break;
                    }
                @endphp
                <p><strong>Client:</strong> {{ $loan->client->name ?? 'Client Not Found' }}</p>
                <p><strong>Principal Amount:</strong> {{ $currency }} {{ number_format($loan->principal_amount, 0) }}</p>
                <p><strong>Processing Fee:</strong> {{ $currency }} {{ number_format($loan->processing_fee, 0) }}</p>
                <p><strong>Total Paid:</strong> <span class="text-success">{{ $currency }} {{ number_format($totalPaid, 0) }}</span></p>
                <p><strong>Remaining Balance:</strong> <span class="text-danger">{{ $currency }} {{ number_format($remainingBalance, 0) }}</span></p>
                <p><strong>Status:</strong> <span class="badge {{ $badgeColor }}">{{ ucfirst($loan->status) }}</span></p>
            </div>
        </div>

        {{-- Collateral Card (CURRENCY FIX APPLIED) --}}
        <div class="card mb-4 shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Collateral</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Type</th><th>Description</th><th>Valuation ({{ $currency }})</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($loan->collaterals as $collateral)
                            <tr>
                                <td>{{ $collateral->collateral_type ?? 'N/A' }}</td>
                                <td>{{ $collateral->description ?? 'N/A' }}</td>
                                <td>{{ number_format($collateral->valuation_amount ?? 0, 0) }}</td>
                                <td>@if($collateral->is_released)<span class="badge bg-success">Released</span>@else<span class="badge bg-info">Held</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">No collateral was provided for this loan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Guarantors Card (Unchanged) --}}
        <div class="card mb-4 shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Guarantor(s)</h6>
            </div>
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
        
        {{-- Payment History Card (CURRENCY FIX APPLIED) --}}
        <div class="card mb-4 shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Date</th><th>Amount ({{ $currency }})</th><th>Method</th><th>Receipt #</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($loan->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ number_format($payment->amount ?? 0, 0) }}</td>
                                <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                                <td>{{ $payment->receipt_number ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-secondary btn-sm" target="_blank">Receipt</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No payments have been recorded for this loan yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Repayment Schedule Card (CURRENCY FIX APPLIED) --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Repayment Schedule</h6>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>#</th><th>Due Date</th><th>Payment Amount ({{ $currency }})</th><th>Principal ({{ $currency }})</th><th>Interest ({{ $currency }})</th><th>Balance ({{ $currency }})</th></tr></thead>
                    <tbody>
                        @if(!empty($schedule))
                            @foreach ($schedule as $payment_schedule)
                                <tr>
                                    <td>{{ $payment_schedule['period'] ?? '' }}</td>
                                    <td>{{ isset($payment_schedule['due_date']) ? \Carbon\Carbon::parse($payment_schedule['due_date'])->format('F d, Y') : '' }}</td>
                                    <td>{{ number_format($payment_schedule['payment_amount'] ?? 0, 0) }}</td>
                                    <td>{{ number_format($payment_schedule['principal'] ?? 0, 0) }}</td>
                                    <td>{{ number_format($payment_schedule['interest'] ?? 0, 0) }}</td>
                                    <td>{{ number_format($payment_schedule['balance'] ?? 0, 0) }}</td>
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
        {{-- Payment Modal for THIS specific loan (CURRENCY FIX APPLIED) --}}
        <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
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
                            <div class="mb-3">
                                <label class="form-label">Client Name</label>
                                <input type="text" class="form-control" value="{{ $loan->client->name }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount Paid ({{ $currency }})</label>
                                <input type="number" step="100" class="form-control" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endpush