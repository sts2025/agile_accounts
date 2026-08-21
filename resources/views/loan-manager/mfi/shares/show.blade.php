@extends('layouts.manager')

@section('title', 'Share Account Details')

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
            <p class="mb-0 text-muted">Client: <strong>{{ $account->client->name }}</strong> ({{ $account->client->phone_number }})
                @if($account->status === 'closed')
                    <span class="badge bg-secondary ms-2">Closed</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($account->status !== 'closed')
                <form action="{{ route('mfi.shares.close', $account->id) }}" method="POST" onsubmit="return confirm('Close this share account? {{ $account->units > 0 ? 'All remaining units will be redeemed for cash at the current share value. ' : '' }}This cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger shadow-sm">
                        <i class="fas fa-times-circle me-2"></i> Close Account
                    </button>
                </form>
            @endif
            <a href="{{ route('mfi.shares.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left me-2"></i> Back to Share Accounts
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-circle me-2"></i> <strong>Failed:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
                <div class="card-body py-4 text-center">
                    <h6 class="text-uppercase text-muted fw-bold mb-3">Units Held</h6>
                    <h2 class="display-5 fw-bold text-warning font-monospace mb-1">
                        {{ rtrim(rtrim(number_format($account->units, 4), '0'), '.') }}
                    </h2>
                    <p class="text-muted small mb-4">
                        Value: {{ optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX' }} {{ number_format($account->balance) }}
                        @if($product)
                            ({{ number_format($product->share_value) }} / share)
                        @endif
                    </p>

                    @if($account->status !== 'closed')
                        <div class="d-grid gap-3 mt-2">
                            <button class="btn btn-success btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#buyModal">
                                <i class="fas fa-plus me-2"></i> Buy Shares
                            </button>
                            <button class="btn btn-outline-danger btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#redeemModal">
                                <i class="fas fa-minus me-2"></i> Redeem Shares
                            </button>
                        </div>
                    @else
                        <div class="alert alert-secondary text-center mb-0 mt-2">
                            <i class="fas fa-times-circle me-1"></i> This account is closed.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Share Transaction History</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase sticky-top">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Type</th>
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($account->transactions as $tx)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ \Carbon\Carbon::parse($tx->created_at)->format('d M, Y H:i') }}</td>
                                    <td>
                                        @if($tx->transaction_type == 'share_purchase')
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="fas fa-plus me-1"></i> Purchase</span>
                                        @elseif($tx->transaction_type == 'share_redemption')
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1"><i class="fas fa-minus me-1"></i> Redemption</span>
                                        @elseif($tx->transaction_type == 'dividend')
                                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><i class="fas fa-gift me-1"></i> Dividend</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">{{ ucfirst($tx->transaction_type) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $tx->narration ?? '—' }}</td>
                                    <td class="text-end pe-4 fw-bold font-monospace {{ $tx->transaction_type == 'share_redemption' ? 'text-danger' : 'text-success' }}">
                                        {{ $tx->transaction_type == 'share_redemption' ? '-' : '+' }} {{ number_format($tx->amount) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No transactions found.</td>
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

{{-- BUY SHARES MODAL --}}
<div class="modal fade" id="buyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('mfi.shares.buy', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Buy Shares</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    @if($product)
                        <div class="alert alert-info py-2 small">Price per share: {{ number_format($product->share_value) }}</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Number of Shares</label>
                        <input type="number" step="0.01" min="0.01" name="units" class="form-control form-control-lg text-end fw-bold" required>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Confirm Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- REDEEM SHARES MODAL --}}
<div class="modal fade" id="redeemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('mfi.shares.redeem', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Redeem Shares</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-info py-2 small">
                        Units held: {{ rtrim(rtrim(number_format($account->units, 4), '0'), '.') }}
                        @if($product) &middot; Price per share: {{ number_format($product->share_value) }} @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Number of Shares to Redeem</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $account->units }}" name="units" class="form-control form-control-lg text-end fw-bold" required>
                    </div>
                    <small class="text-muted d-block">Redeeming pays out cash at the current share value and reduces the member's holding.</small>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">Confirm Redemption</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
