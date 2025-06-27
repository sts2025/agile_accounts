@extends('layouts.app')

@section('title', 'My Loans')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Loans</h1>
        <a href="{{ route('loans.create') }}" class="btn btn-primary">Create New Loan</a>
    </div>
    
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Find a Loan</div>
        <div class="card-body">
            <form method="GET" action="{{ route('loans.index') }}">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by client's name..." value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Principal Amount (UGX)</th>
                        <th>Interest Rate (%)</th>
                        <th>Term (Months)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td>{{ $loan->client->name }}</td>
                            <td>{{ number_format($loan->principal_amount, 2) }}</td>
                            <td>{{ $loan->interest_rate }}%</td>
                            <td>{{ $loan->term }}</td>
                            <td>
                                @php
                                    $badgeColor = 'bg-secondary';
                                    switch ($loan->status) {
                                        case 'active': $badgeColor = 'bg-primary'; break;
                                        case 'paid': $badgeColor = 'bg-success'; break;
                                        case 'defaulted': $badgeColor = 'bg-danger'; break;
                                        case 'pending': $badgeColor = 'bg-warning text-dark'; break;
                                    }
                                @endphp
                                <span class="badge {{ $badgeColor }}">{{ ucfirst($loan->status) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-info btn-sm">View</a>
                                <a href="{{ route('loans.edit', $loan->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('loans.destroy', $loan->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to permanently delete this loan and all its history? This action cannot be undone.');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No loans match your search or you have not created any loans yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection