@extends('layouts.manager')

@section('title', 'Manage Branches')

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Branches / Offices</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Your Branches</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Phone</th>
                                    <th class="text-center">Staff</th>
                                    <th class="text-center">Clients</th>
                                    <th class="text-center">Loans</th>
                                    <th class="text-center">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                <tr>
                                    <td>
                                        <strong>{{ $branch->name }}</strong>
                                        @if($branch->address)
                                            <div class="text-muted small">{{ $branch->address }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $branch->code ?? '—' }}</td>
                                    <td>{{ $branch->phone ?? '—' }}</td>
                                    <td class="text-center">{{ $branch->staff_count }}</td>
                                    <td class="text-center">{{ $branch->clients_count }}</td>
                                    <td class="text-center">{{ $branch->loans_count }}</td>
                                    <td class="text-center">
                                        @if($branch->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editBranch{{ $branch->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('manager.branches.toggle', $branch->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" title="{{ $branch->is_active ? 'Deactivate' : 'Re-activate' }}">
                                                <i class="fas {{ $branch->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('manager.branches.destroy', $branch->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this branch? Only possible if nothing is assigned to it.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit modal --}}
                                <div class="modal fade" id="editBranch{{ $branch->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('manager.branches.update', $branch->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Branch</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Branch Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $branch->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Code</label>
                                                        <input type="text" name="code" class="form-control" value="{{ $branch->code }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Phone</label>
                                                        <input type="text" name="phone" class="form-control" value="{{ $branch->phone }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Address</label>
                                                        <textarea name="address" class="form-control" rows="2">{{ $branch->address }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No branches yet. Everything is currently treated as one head office — add a branch here once you're ready to split staff, clients, and loans by location.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Add New Branch</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('manager.branches.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Branch Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Code <span class="text-muted small">(optional, e.g. "HQ", "MBR")</span></label>
                            <input type="text" name="code" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Branch</button>
                    </form>
                    <p class="text-muted small mt-3 mb-0">
                        Once you have branches, you can assign staff, clients, and loans to them from their own create/edit forms, and filter reports by branch.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
