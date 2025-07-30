@extends('layouts.manager')

@section('title', 'Dashboard')

@section('content')
    <h2 class="mb-4">Welcome, {{ Auth::user()->name }}!</h2>
    
    @if($latestMessage)
        <div class="alert alert-info mb-4">
            <h4 class="alert-heading">{{ $latestMessage->title }}</h4>
            <p>{{ $latestMessage->body }}</p>
            <hr>
            <p class="mb-0"><small>Posted on: {{ $latestMessage->created_at->format('d F, Y') }}</small></p>
        </div>
    @endif
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Clients</h5>
                    <p class="card-text fs-4">{{ $clientCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Active Loans</h5>
                    <p class="card-text fs-4">{{ $activeLoanCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Loaned Amount</h5>
                    <p class="card-text fs-4">UGX {{ number_format($totalLoanedAmount, 0) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection