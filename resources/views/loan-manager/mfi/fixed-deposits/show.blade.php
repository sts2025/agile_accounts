@extends('layouts.manager')

@section('title', 'Fixed Deposit Details')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold">
                Account: <span class="font-monospace text-primary">{{ $account->account_number }}</span>
                @if($account->nickname)
                    <span class="text-muted">— {{ $account->nickname }}</span>
                @endif
            </h1>
            <p class="mb-0 text-muted">Client: <strong>{{ $account->client->name }}</strong> ({{ $account->client->phone_number }})</p>
        </div>
        <a href="{{ route('mfi.fixed-deposits.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Fixed Deposits
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

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
                <div class="card-body py-4 text-center">
                    <h6 class="text-uppercase text-muted fw-bold mb-3">Principal</h6>
                    <h2 class="display-6 fw-bold text-dark font-monospace mb-3">
                        {{ optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX' }} {{ number_format($account->principal_amount) }}
                    </h2>

                    <div class="text-start small border-top pt-3 mt-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Interest rate</span>
                            <span class="fw-bold">{{ $product ? number_format($product->interest_rate, 2) : 0 }}% (full term)</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Maturity date</span>
                            <span class="fw-bold">{{ $account->maturity_date ? \Carbon\Carbon::parse($account->maturity_date)->format('d M, Y') : 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Status</span>
                            <span class="fw-bold">
                                @if($account->status == 'closed')
                                    Closed
                                @elseif($isMatured)
                                    Matured
                                @else
                                    Active ({{ \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($account->maturity_date), false) }} days to maturity)
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Projected interest</span>
                            <span class="fw-bold text-success">{{ number_format($projectedInterest) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fw-bold">Projected payout</span>
                            <span class="fw-bold text-success">{{ number_format($projectedPayout) }}</span>
                        </div>
                    </div>

                    @if($account->status === 'active')
                        <div class="d-grid gap-2 mt-4">
                            <button class="btn {{ $isMatured ? 'btn-success' : 'btn-outline-danger' }} btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#closeModal">
                                <i class="fas fa-{{ $isMatured ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                                {{ $isMatured ? 'Close & Pay Out' : 'Close Early' }}
                            </button>
                            @if(!$isMatured && $product)
                                <small class="text-muted">Closing before maturity forfeits {{ number_format($product->early_withdrawal_penalty_percent, 0) }}% of the interest earned so far. Principal is never at risk.</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Transaction History</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase sticky-top">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($account->transactions as $tx)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ \Carbon\Carbon::parse($tx->created_at)->format('d M, Y H:i') }}</td>
                                    <td class="text-muted small">{{ $tx->narration ?? '—' }}</td>
                                    <td class="text-end pe-4 fw-bold font-monospace {{ $tx->transaction_type == 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                        {{ $tx->transaction_type == 'withdrawal' ? '-' : '+' }} {{ number_format($tx->amount) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No transactions found.</td>
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

{{-- CLOSE MODAL --}}
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('mfi.fixed-deposits.close', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header {{ $isMatured ? 'bg-success' : 'bg-danger' }} text-white">
                    <h5 class="modal-title fw-bold">{{ $isMatured ? 'Close & Pay Out' : 'Close Early' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <p>
                        This pays <strong>{{ number_format($projectedPayout) }}</strong> (principal + interest{{ $isMatured ? '' : ', penalty-adjusted' }})
                        directly into the client's savings account and closes this deposit.
                    </p>
                    @if(!$isMatured)
                        <p class="text-danger small mb-0">This deposit has not yet matured — the early withdrawal penalty will be applied to the interest.</p>
                    @endif
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn {{ $isMatured ? 'btn-success' : 'btn-danger' }} fw-bold px-4">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
