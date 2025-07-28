@extends('layouts.app')

@section('title', 'My Clients')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Clients</h1>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">Add New Client</a>
    </div>
    
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Find a Client</div>
        <div class="card-body">
            <form method="GET" action="{{ route('clients.index') }}">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}">
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
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Occupation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->phone_number }}</td>
                            <td>{{ $client->address }}</td>
                            <td>{{ $client->business_occupation ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('clients.destroy', $client->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this client?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No clients match your search or you have not added any clients yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection