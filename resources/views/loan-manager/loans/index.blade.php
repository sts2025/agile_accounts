@extends('layouts.manager')

@section('title', 'My Loans')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        {{-- The title is now simply "Loans" --}}
        <h1>Loans</h1>
        <div>
            {{-- We add a button to manage clients from here --}}
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">Manage Clients</a>
            <a href="{{ route('loans.create') }}" class="btn btn-primary">Create New Loan</a>
        </div>
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
                        <th>Term</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td>
                                {{-- We now link to the client's edit page from here --}}
                                <a href="{{ route('clients.edit', $loan->client->id) }}">{{ $loan->client->name ?? 'N/A' }}</a>
                                <br>
                                <small class="text-muted">{{ $loan->client->phone_number ?? '' }}</small>
                            </td>
                            <td>{{ number_format($loan->principal_amount, 0) }}</td>
                            <td>{{ $loan->interest_rate }}%</td>
                            <td>{{ $loan->term }}</td>
                            <td>{{ $loan->repayment_frequency }}</td>
                            <td>
                                @php
                                    $badgeColor = 'bg-secondary';
                                    switch ($loan->status) {
                                        case 'active': $badgeColor = 'bg-primary'; break;
                                        case 'paid': $badgeColor = 'bg-success'; break;
                                        case 'defaulted': $badgeColor = 'bg-danger'; break;
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
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No loans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection