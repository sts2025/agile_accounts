@extends('layouts.app')
@section('title', 'Edit Payment')
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Payment Record #{{ $payment->receipt_number }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payments.update', $payment->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3"><label class="form-label">Amount Paid (UGX)</label><input type="number" step="0.01" class="form-control" name="amount_paid" value="{{ $payment->amount_paid }}" required></div>
                        <div class="mb-3"><label class="form-label">Payment Date</label><input type="date" class="form-control" name="payment_date" value="{{ $payment->payment_date->format('Y-m-d') }}" required></div>
                        <div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><option value="Cash" {{ $payment->payment_method == 'Cash' ? 'selected' : '' }}>Cash</option><option value="Bank Transfer" {{ $payment->payment_method == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option><option value="Mobile Money" {{ $payment->payment_method == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option></select></div>
                        <div class="mb-3"><label class="form-label">Notes (Optional)</label><textarea class="form-control" name="notes" rows="3">{{ $payment->notes }}</textarea></div>
                        <button type="submit" class="btn btn-primary">Update Payment</button>
                        <a href="{{ route('loans.show', $payment->loan_id) }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection