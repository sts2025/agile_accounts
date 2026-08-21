@extends('layouts.manager')

@section('title', 'Client Groups')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-users text-primary me-2"></i> Client Groups</h1>
        <a href="{{ route('client-groups.create') }}" class="btn btn-success shadow-sm">
            <i class="fas fa-plus me-2"></i> New Group
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Group Name</th>
                            <th>Description</th>
                            <th class="text-center">Members</th>
                            <th class="text-center">Loans Issued</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $group->name }}</td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($group->description, 60) ?: '—' }}</td>
                                <td class="text-center">{{ $group->members_count }}</td>
                                <td class="text-center">{{ $group->loans_count }}</td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('client-groups.show', $group->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('client-groups.edit', $group->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    No client groups yet. <a href="{{ route('client-groups.create') }}">Create one</a> to start issuing group loans.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
