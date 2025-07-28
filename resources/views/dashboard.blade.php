@extends('layouts.app')

@section('title', 'Loan Manager Dashboard')

@section('content')
    <h2 class="mb-4">Welcome, {{ Auth::user()->name }}!</h2>
    <div class="container mt-4">
    
  
        @if($latestMessage)
            <div class="alert alert-info mb-4">
                <h4 class="alert-heading">{{ $latestMessage->title }}</h4>
                <p>{{ $latestMessage->body }}</p>
                <hr>
                <p class="mb-0"><small>Posted on: {{ $latestMessage->created_at->format('d F, Y') }}</small></p>
            </div>
        @endif
        <div class="row">
            ```


    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Clients</h5>
                    <p class="card-text fs-4">{{ $clientCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Active Loans</h5>
                    <p class="card-text fs-4">{{ $activeLoanCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Loaned Amount</h5>
                    <p class="card-text fs-4">UGX {{ number_format($totalLoanedAmount, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <hr>

    <h3>Quick Actions</h3>
    <a href="{{ route('clients.index') }}" class="btn btn-lg btn-secondary">Manage Clients</a>
    <a href="{{ route('loans.index') }}" class="btn btn-lg btn-secondary">Manage Loans</a>
    <a href="{{ route('manager.reports.trial-balance') }}" class="btn btn-lg btn-info">View Trial Balance</a>
    <a href="{{ route('manager.reports.profit-and-loss') }}" class="btn btn-lg btn-info">View P&L Statement</a>
    <a href="{{ route('manager.reports.balance-sheet') }}" class="btn btn-lg btn-info">View Balance Sheet</a>
    <a href="{{ route('manager.reports.aging-analysis') }}" class="btn btn-lg btn-warning">Loan Aging Report</a>
@endsection