@extends('layouts.manager')

@section('title', $group->name)

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-users text-primary me-2"></i> {{ $group->name }}</h1>
            @if($group->description)
                <p class="text-muted mb-0">{{ $group->description }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('client-groups.edit', $group->id) }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-edit me-2"></i> Edit Group
            </a>
            <a href="{{ route('loans.create', ['group' => $group->id]) }}" class="btn btn-success shadow-sm">
                <i class="fas fa-plus me-2"></i> Issue Group Loan
            </a>
        </div>
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

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-friends me-2"></i> Members ({{ $group->members->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Name</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group->members as $member)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('clients.show', $member->id) }}">{{ $member->name }}</a>
                                        </td>
                                        <td class="text-muted small">{{ $member->phone_number }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4 text-muted">
                                            No members yet. <a href="{{ route('client-groups.edit', $group->id) }}">Add some</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-2"></i> Loans Issued to This Group</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Representative</th>
                                    <th class="text-end">Principal</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center pe-4">Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group->loans as $loan)
                                    <tr>
                                        <td class="ps-4">{{ $loan->client->name ?? 'Unknown' }}</td>
                                        <td class="text-end font-monospace">{{ number_format($loan->principal_amount) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $loan->status === 'active' ? 'success' : ($loan->status === 'paid' ? 'primary' : 'secondary') }} bg-opacity-10 text-{{ $loan->status === 'active' ? 'success' : ($loan->status === 'paid' ? 'primary' : 'secondary') }}">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center pe-4 small text-muted">{{ $loan->reference_id }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No loans issued to this group yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
