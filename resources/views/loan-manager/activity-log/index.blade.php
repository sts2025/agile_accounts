@extends('layouts.manager')

@section('title', 'Activity Log')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-history text-secondary me-2"></i> Activity Log</h1>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('activity-log.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3 col-6">
                    <label class="form-label small fw-bold text-dark">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small fw-bold text-dark">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-dark">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All</option>
                        @foreach (['created', 'updated', 'deleted'] as $a)
                            <option value="{{ $a }}" {{ ($filters['action'] ?? '') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small fw-bold text-dark">Record Type</label>
                    <select name="subject_type" class="form-select">
                        <option value="">All</option>
                        @foreach ($subjectTypes as $type)
                            <option value="{{ $type }}" {{ ($filters['subject_type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 col-6">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">When</th>
                            <th>By</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>Description</th>
                            <th class="pe-3">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="ps-3 small text-muted">{{ $log->created_at?->format('d-M-Y H:i') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>
                                    @php
                                        $badge = match($log->action) {
                                            'created' => 'success',
                                            'updated' => 'info',
                                            'deleted' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($log->action) }}</span>
                                </td>
                                <td>{{ $log->subject_type }} #{{ $log->subject_id }}</td>
                                <td>{{ $log->description }}</td>
                                <td class="pe-3">
                                    @if (!empty($log->changes))
                                        <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#changes-{{ $log->id }}">
                                            {{ count($log->changes) }} field(s)
                                        </button>
                                        <div class="collapse" id="changes-{{ $log->id }}">
                                            <ul class="small mb-0 mt-1">
                                                @foreach ($log->changes as $field => $vals)
                                                    <li><strong>{{ $field }}</strong>: {{ $vals['old'] ?? 'null' }} &rarr; {{ $vals['new'] ?? 'null' }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
