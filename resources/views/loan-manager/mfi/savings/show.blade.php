@extends('layouts.manager')

@section('title', 'Account Details')

@section('content')
<div class="container-fluid px-0">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold">
                Account: <span class="font-monospace text-primary">{{ $account->account_number }}</span>
                @if($account->nickname)
                    <span class="text-muted">— {{ $account->nickname }}</span>
                @endif
                @if($account->status === 'on_hold')
                    <span class="badge bg-warning text-dark ms-2">On Hold</span>
                @elseif($account->status === 'closed')
                    <span class="badge bg-secondary ms-2">Closed</span>
                @endif
            </h1>
            <p class="mb-0 text-muted">Client: <strong>{{ $account->client->name }}</strong> ({{ $account->client->phone_number }})</p>
        </div>
        <div class="d-flex gap-2">
            @if($account->status === 'active')
                <form action="{{ route('mfi.savings.hold', $account->id) }}" method="POST" onsubmit="return confirm('Put this account on hold? Deposits and withdrawals will be blocked until it is taken off hold.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning shadow-sm">
                        <i class="fas fa-pause-circle me-2"></i> Put on Hold
                    </button>
                </form>
            @elseif($account->status === 'on_hold')
                <form action="{{ route('mfi.savings.unhold', $account->id) }}" method="POST" onsubmit="return confirm('Take this account off hold and resume normal transactions?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-success shadow-sm">
                        <i class="fas fa-play-circle me-2"></i> Take Off Hold
                    </button>
                </form>
            @endif

            @if($account->status !== 'closed')
                <form action="{{ route('mfi.savings.close', $account->id) }}" method="POST" onsubmit="return confirm('Close this account? {{ $account->balance > 0 ? 'The remaining balance will be paid out as a final withdrawal. ' : '' }}This cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger shadow-sm">
                        <i class="fas fa-times-circle me-2"></i> Close Account
                    </button>
                </form>
            @endif

            <a href="{{ route('mfi.savings.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left me-2"></i> Back to Accounts
            </a>
        </div>
    </div>

    {{-- Alerts & Error Handling --}}
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
            <i class="fas fa-exclamation-circle me-2"></i> <strong>Transaction Failed:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- LEFT COLUMN: Balance & Actions --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body py-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3 text-center">Account Balance</h6>
                    <div class="text-center mb-4">
                        <h2 class="display-5 fw-bold text-success font-monospace mb-1">
                            {{ optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX' }} {{ number_format($account->balance) }}
                        </h2>
                        
                        {{-- MFI LOGIC ADDED: Showing locked vs available funds --}}
                        @if($account->lien_amount > 0)
                            <small class="text-danger d-block">
                                <i class="fas fa-lock"></i> Locked as Loan Security: UGX {{ number_format($account->lien_amount) }}
                            </small>
                            <small class="text-success fw-bold d-block mt-1">
                                Available to Withdraw: UGX {{ number_format($account->balance - $account->lien_amount) }}
                            </small>
                        @endif
                    </div>
                    
                    @if($account->status === 'active')
                        <div class="d-grid gap-3 mt-4">
                            <button class="btn btn-success btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                                <i class="fas fa-arrow-down me-2"></i> Deposit Cash
                            </button>
                            <button class="btn btn-warning btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                                <i class="fas fa-arrow-up me-2"></i> Withdraw Cash
                            </button>
                        </div>
                    @elseif($account->status === 'on_hold')
                        <div class="alert alert-warning text-center mb-0 mt-4">
                            <i class="fas fa-pause-circle me-1"></i> This account is on hold. Transactions are blocked.
                        </div>
                    @else
                        <div class="alert alert-secondary text-center mb-0 mt-4">
                            <i class="fas fa-times-circle me-1"></i> This account is closed.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Transaction History --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Passbook / Statement</h6>
                    <a href="{{ route('mfi.savings.passbook', $account->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print"></i> Print Passbook
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase sticky-top">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Type</th>
                                    <th>Ref/Method</th>
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($account->transactions as $tx)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ \Carbon\Carbon::parse($tx->created_at)->format('d M, Y H:i') }}</td>
                                    <td>
                                        @if($tx->transaction_type == 'deposit')
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="fas fa-plus me-1"></i> Deposit</span>
                                        @elseif($tx->transaction_type == 'withdrawal')
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1"><i class="fas fa-minus me-1"></i> Withdraw</span>
                                        @elseif($tx->transaction_type == 'dividend')
                                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><i class="fas fa-gift me-1"></i> Dividend</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">{{ ucfirst($tx->transaction_type) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $tx->reference_number ?? 'N/A' }}</td>
                                    <td class="text-muted small">{{ $tx->narration ?? '—' }}</td>
                                    <td class="text-end pe-4 fw-bold font-monospace {{ $tx->transaction_type == 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                        {{ $tx->transaction_type == 'withdrawal' ? '-' : '+' }} {{ number_format($tx->amount) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No transactions found.</td>
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

{{-- DEPOSIT MODAL --}}
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            {{-- CORRECTED FORM ACTION --}}
            <form action="{{ route('mfi.savings.deposit') }}" method="POST">
                @csrf
                {{-- REQUIRED FIELD FOR THE CONTROLLER --}}
                <input type="hidden" name="savings_account_id" value="{{ $account->id }}">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Deposit Cash</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Deposit</label>
                        <input type="number" step="any" name="amount" class="form-control form-control-lg text-end fw-bold font-monospace text-success border-success" required min="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Reference (Mobile Money ID, Receipt No.)</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. MTN-123456789">
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn-light btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Confirm Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- WITHDRAW MODAL --}}
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            {{-- CORRECTED FORM ACTION --}}
            <form action="{{ route('mfi.savings.withdraw') }}" method="POST">
                @csrf
                {{-- REQUIRED FIELD FOR THE CONTROLLER --}}
                <input type="hidden" name="savings_account_id" value="{{ $account->id }}">
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">Withdraw Cash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-info py-2 small">
                        <strong>Available to Withdraw:</strong> {{ optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX' }} {{ number_format($account->balance - $account->lien_amount) }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Withdraw</label>
                        {{-- MAX value is now the available balance (Balance - Lien) --}}
                        <input type="number" step="any" name="amount" class="form-control form-control-lg text-end fw-bold font-monospace text-danger border-warning" required min="1000" max="{{ $account->balance - $account->lien_amount }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Reference / Narration</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. School fees withdrawal">
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn-light btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4">Confirm Withdrawal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection