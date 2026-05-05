@extends('layouts.manager')

@section('title', 'Account Details')

@section('content')
<div class="container-fluid px-0">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold">
                Account: <span class="font-monospace text-primary">{{ $account->account_number }}</span>
            </h1>
            <p class="mb-0 text-muted">Client: <strong>{{ $account->client->name }}</strong> ({{ $account->client->phone_number }})</p>
        </div>
        <a href="{{ route('mfi.savings.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Accounts
        </a>
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
                    
                    <div class="d-grid gap-3 mt-4">
                        <button class="btn btn-success btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="fas fa-arrow-down me-2"></i> Deposit Cash
                        </button>
                        <button class="btn btn-warning btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="fas fa-arrow-up me-2"></i> Withdraw Cash
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Transaction History --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Passbook / Statement</h6>
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
                                    <th>Ref/Method</th>
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($account->transactions as $tx)
                                <tr>
                                    {{-- Use created_at as we changed the schema slightly --}}
                                    <td class="ps-4 text-muted small">{{ \Carbon\Carbon::parse($tx->created_at)->format('d M, Y H:i') }}</td>
                                    <td>
                                        @if($tx->type == 'deposit')
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="fas fa-plus me-1"></i> Deposit</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1"><i class="fas fa-minus me-1"></i> Withdraw</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $tx->reference ?? 'N/A' }}</td>
                                    <td class="text-muted small">Via Controller Logic</td>
                                    <td class="text-end pe-4 fw-bold font-monospace {{ $tx->type == 'deposit' ? 'text-success' : 'text-danger' }}">
                                        {{ $tx->type == 'deposit' ? '+' : '-' }} {{ number_format($tx->amount) }}
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