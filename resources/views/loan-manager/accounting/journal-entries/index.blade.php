@extends('layouts.manager')

@section('title', 'General Journal')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-pen-fancy text-primary me-2"></i> General Journal</h1>
        <a href="{{ route('journal-entries.create') }}" class="btn btn-primary fw-bold shadow-sm">
            <i class="fas fa-plus me-1"></i> New Entry
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
                            <th class="ps-4">Date</th>
                            <th>Reference</th>
                            <th>Narration</th>
                            <th class="text-center">Source</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td class="ps-4">{{ \Carbon\Carbon::parse($entry->entry_date)->format('d M Y') }}</td>
                                <td class="text-muted small">{{ $entry->reference_no ?: '—' }}</td>
                                <td class="text-truncate" style="max-width:280px;">{{ $entry->narration ?: '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-muted border">{{ $entry->source === 'manual' ? 'Manual' : $entry->source }}</span>
                                </td>
                                <td class="text-end font-monospace">{{ number_format($entry->debit_sum, 2) }}</td>
                                <td class="text-center">
                                    @if($entry->is_reversed)
                                        <span class="badge bg-warning text-dark">Reversed</span>
                                    @elseif($entry->reverses_journal_entry_id)
                                        <span class="badge bg-info text-dark">Reversal</span>
                                    @else
                                        <span class="badge bg-success">Posted</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('journal-entries.show', $entry->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No journal entries yet. <a href="{{ route('journal-entries.create') }}">Post your first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $entries->links() }}
    </div>
</div>
@endsection
