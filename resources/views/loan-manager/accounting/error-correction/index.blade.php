@extends('layouts.manager')

@section('title', 'Error Correction')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-eraser text-danger me-2"></i> Error Correction</h1>
    </div>

    <div class="alert alert-info shadow-sm border-0">
        Nothing here is deleted silently. Correcting a payment keeps the original record and logs what changed;
        reversing a journal entry posts a new offsetting entry rather than editing the original. Find the item you
        need to fix below.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-money-bill-wave me-2"></i> Recent Loan Repayments</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Client</th>
                            <th>Receipt</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td class="ps-4">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                                <td>{{ optional($payment->loan->client)->name ?? '—' }}</td>
                                <td class="text-muted small">{{ $payment->receipt_number ?: '—' }}</td>
                                <td class="text-end font-monospace">{{ number_format($payment->amount_paid, 2) }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-sm btn-outline-secondary">Correct / Delete</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-pen-fancy me-2"></i> Recent Journal Entries</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Narration</th>
                            <th class="text-center">Source</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentJournalEntries as $entry)
                            <tr>
                                <td class="ps-4">{{ \Carbon\Carbon::parse($entry->entry_date)->format('d M Y') }}</td>
                                <td class="text-truncate" style="max-width:320px;">{{ $entry->narration ?: '—' }}</td>
                                <td class="text-center"><span class="badge bg-light text-muted border">{{ $entry->source === 'manual' ? 'Manual' : $entry->source }}</span></td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('journal-entries.show', $entry->id) }}" class="btn btn-sm btn-outline-secondary">View / Reverse</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No journal entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
