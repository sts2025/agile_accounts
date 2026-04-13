@extends('layouts.manager')

@section('title', 'Manage Payments')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Payments & Transactions</h1>
        <div class="btn-group">
            <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#splitPaymentModal">
                <i class="fas fa-plus me-2"></i> Record Split Payment
            </button>
            <button onclick="window.print()" class="btn btn-secondary shadow-sm">
                <i class="fas fa-print me-2"></i> Print
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 border-start border-5 border-success shadow-sm">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 border-start border-5 border-danger shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Transaction History (Split Breakdown)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Client / Loan</th>
                            <th class="text-end text-primary">Principal</th>
                            <th class="text-end text-warning">Interest</th>
                            <th class="text-end fw-bold">Total</th>
                            <th>Method</th>
                            <th class="text-center no-print">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="ps-4 text-muted small">
                                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $payment->loan->client->name ?? 'Unknown' }}</div>
                                    <div class="small text-muted">Ref: {{ $payment->receipt_number ?? 'N/A' }}</div>
                                </td>
                                <td class="text-end text-primary font-monospace">
                                    {{ number_format($payment->principal_paid, 0) }}
                                </td>
                                <td class="text-end text-warning font-monospace">
                                    {{ number_format($payment->interest_paid, 0) }}
                                </td>
                                <td class="text-end fw-bold font-monospace text-dark bg-light">
                                    {{ number_format($payment->amount_paid, 0) }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ ucfirst($payment->payment_method) }}</span>
                                </td>
                                <td class="text-center no-print">
                                    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-sm btn-outline-secondary" title="Print Receipt">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No payments recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>

{{-- SPLIT PAYMENT MODAL --}}
<div class="modal fade" id="splitPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Record Split Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <div class="modal-body bg-light">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Select Loan</label>
                        <select name="loan_id" class="form-select shadow-sm" required>
                            <option value="" disabled selected>Choose client loan...</option>
                            @foreach($loans as $loan)
                                <option value="{{ $loan->id }}">
                                    {{ $loan->client->name }} 
                                    (Bal: {{ number_format($loan->principal_amount + ($loan->principal_amount * ($loan->interest_rate/100)) - $loan->payments->sum('amount_paid')) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Date</label>
                            <input type="date" name="payment_date" class="form-control shadow-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Method</label>
                            <select name="payment_method" class="form-select shadow-sm">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Money">Mobile Money</option>
                            </select>
                        </div>
                    </div>

                    <div class="card p-3 border-success bg-white shadow-sm">
                        <h6 class="text-success fw-bold mb-3 border-bottom pb-2">Payment Breakdown</h6>
                        
                        <div class="row mb-2">
                            <div class="col-7"><label class="mb-0 fw-bold">Principal Paid:</label></div>
                            <div class="col-5">
                                <input type="number" name="principal_paid" id="inputPrincipal" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calculateTotal()">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-7"><label class="mb-0 fw-bold">Interest Paid:</label></div>
                            <div class="col-5">
                                <input type="number" name="interest_paid" id="inputInterest" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calculateTotal()">
                            </div>
                        </div>

                        <div class="row mt-3 pt-2 border-top">
                            <div class="col-7"><label class="mb-0 fw-bold text-uppercase">Total:</label></div>
                            <div class="col-5 text-end"><h4 class="mb-0 fw-bold text-success" id="displayTotal">0.00</h4></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small text-muted">Reference / Notes</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional details">
                    </div>

                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function calculateTotal() {
        let principal = parseFloat(document.getElementById('inputPrincipal').value) || 0;
        let interest = parseFloat(document.getElementById('inputInterest').value) || 0;
        document.getElementById('displayTotal').innerText = (principal + interest).toLocaleString(undefined, {minimumFractionDigits: 2});
    }
</script>
@endpush

@endsection