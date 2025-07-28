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
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <hr>
    {{-- Loan Summary Card --}}
    <div class="card mb-4">
        {{-- ... All the summary details ... --}}
    </div>
    {{-- Collateral Card --}}
    <div class="card mb-4">
        {{-- ... Collateral table ... --}}
    </div>
    {{-- Guarantors Card --}}
    <div class="card mb-4">
        {{-- ... Guarantors table ... --}}
    </div>
    {{-- Payment History Card --}}
    <div class="card mb-4">
        {{-- ... Payment History table with "Receipt" button --}}
    </div>
    {{-- Repayment Schedule Card --}}
    <div class="card">
        {{-- ... Repayment Schedule table ... --}}
    </div>
@endsection

@push('modals')
    {{-- Only the Payment Modal is now needed on this page --}}
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
@endpush