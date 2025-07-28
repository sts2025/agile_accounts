@extends('layouts.app')
@section('title', 'Confirm Password')
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Confirm Password to Proceed</div>
                <div class="card-body">
                    <p class="text-muted">For your security, please confirm your password to edit this payment record.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first('password') }}</div>
                    @endif

                    <form method="POST" action="{{ route('payments.password.confirm', $payment->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Confirm & Continue</button>
                        <a href="{{ route('loans.show', $payment->loan_id) }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection