@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Payment</h1>
        <a href="{{ route('loans.show', $payment->loan_id) }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left"></i> Back to Loan
        </a>
    </div>

    <div class="card shadow mb-4" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Edit Payment Record #{{ $payment->reference_id ?? $payment->id }}
            </h6>
        </div>
        <div class="card-body">
            
            {{-- Error Display --}}
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('payments.update', $payment->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Amount Paid ({{ Auth::user()->loanManager->currency_symbol ?? 'UGX' }})</label>
                    {{-- FIX: Ensure value is raw number (no commas) for input type="number" --}}
                    <input type="number" 
                           step="0.01" 
                           class="form-control" 
                           name="amount_paid" 
                           value="{{ number_format($payment->amount_paid, 2, '.', '') }}" 
                           required>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Payment Date</label>
                    <input type="date" 
                           class="form-control" 
                           name="payment_date" 
                           value="{{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}" 
                           required>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Payment Method</label>
                    <select name="payment_method" class="form-control">
                        @php $method = $payment->payment_method; @endphp
                        <option value="Cash" {{ $method == 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="Bank Transfer" {{ $method == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="Mobile Money" {{ $method == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="Cheque" {{ $method == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="Other" {{ $method == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Reference ID (Receipt No)</label>
                    <input type="text" class="form-control" name="reference_id" value="{{ $payment->reference_id }}">
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Notes (Optional)</label>
                    <textarea class="form-control" name="notes" rows="3">{{ $payment->notes }}</textarea>
                </div>

                <div class="d-flex justify-content-between">
                    {{-- Delete Option --}}
                    <button type="button" class="btn btn-outline-danger" 
                            onclick="if(confirm('Are you sure you want to delete this payment permanently? This will adjust the loan balance.')) document.getElementById('delete-payment-form').submit();">
                        <i class="fas fa-trash"></i> Delete
                    </button>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save"></i> Update Payment
                    </button>
                </div>
            </form>

            {{-- Hidden Delete Form --}}
            <form id="delete-payment-form" action="{{ route('payments.destroy', $payment->id) }}" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
            </form>

        </div>
    </div>
</div>
@endsection