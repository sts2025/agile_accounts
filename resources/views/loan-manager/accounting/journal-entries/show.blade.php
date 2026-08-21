@extends('layouts.manager')

@section('title', 'Journal Entry #' . $entry->id)

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Journal Entry #{{ $entry->id }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('journal-entries.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
            @if(!$entry->is_reversed && !$entry->reverses_journal_entry_id)
                <form action="{{ route('journal-entries.reverse', $entry->id) }}" method="POST" onsubmit="return confirm('Post a reversing entry for this one? The original stays in your records, marked as reversed.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger shadow-sm">
                        <i class="fas fa-undo me-2"></i> Reverse
                    </button>
                </form>
            @endif
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

    @if($entry->is_reversed)
        <div class="alert alert-warning shadow-sm border-0">
            This entry has been reversed
            @if($entry->reversal)
                by <a href="{{ route('journal-entries.show', $entry->reversal->id) }}">entry #{{ $entry->reversal->id }}</a>.
            @else
                .
            @endif
        </div>
    @endif
    @if($entry->reverses)
        <div class="alert alert-info shadow-sm border-0">
            This entry reverses <a href="{{ route('journal-entries.show', $entry->reverses->id) }}">entry #{{ $entry->reverses->id }}</a>.
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <div class="text-muted small text-uppercase">Date</div>
                    <div class="fw-bold">{{ \Carbon\Carbon::parse($entry->entry_date)->format('d M Y') }}</div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="text-muted small text-uppercase">Reference</div>
                    <div class="fw-bold">{{ $entry->reference_no ?: '—' }}</div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="text-muted small text-uppercase">Source</div>
                    <div class="fw-bold">{{ $entry->source === 'manual' ? 'Manual' : $entry->source }}</div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="text-muted small text-uppercase">Posted By</div>
                    <div class="fw-bold">{{ optional($entry->createdBy)->name ?? 'System' }}</div>
                </div>
                <div class="col-12 mt-2">
                    <div class="text-muted small text-uppercase">Narration</div>
                    <div>{{ $entry->narration ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Account</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end pe-4">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entry->lines as $line)
                            <tr>
                                <td class="ps-4">{{ $line->account->code }} — {{ $line->account->name }}</td>
                                <td class="text-muted small">{{ $line->description ?: '—' }}</td>
                                <td class="text-end font-monospace">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                                <td class="text-end pe-4 font-monospace">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold border-top">
                            <td colspan="2" class="ps-4">Totals</td>
                            <td class="text-end font-monospace">{{ number_format($entry->total_debit, 2) }}</td>
                            <td class="text-end pe-4 font-monospace">{{ number_format($entry->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
