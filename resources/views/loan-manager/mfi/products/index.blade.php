@extends('layouts.manager')

@section('title', 'Product Settings')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-sliders-h text-warning me-2"></i> Product Settings</h1>
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

    {{-- LOAN PRODUCTS --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-2"></i> Loan Products</h6>
            <a href="{{ route('mfi.products.create', ['type' => 'loan']) }}" class="btn btn-sm btn-primary fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Add Loan Product
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th class="text-center">Collateral Required</th>
                            <th class="text-center">Guarantor Required</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loanProducts as $product)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $product->name }}</td>
                            <td class="text-center font-monospace">{{ number_format($product->collateral_ratio * 100, 0) }}% of principal</td>
                            <td class="text-center">
                                @if($product->requires_guarantor)
                                    <span class="badge bg-info bg-opacity-10 text-info">Required</span>
                                @else
                                    <span class="badge bg-light text-muted">Optional</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($product->is_active)
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('mfi.products.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('mfi.products.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $product->is_active ? 'danger' : 'success' }}">
                                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No loan products configured yet — new loans will use the default 30% collateral rule.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SAVINGS PRODUCTS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-piggy-bank me-2"></i> Savings Products</h6>
            <a href="{{ route('mfi.products.create', ['type' => 'savings']) }}" class="btn btn-sm btn-success fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Add Savings Product
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th class="text-center">Interest Rate</th>
                            <th class="text-center">Min. Balance</th>
                            <th class="text-center">Compulsory</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($savingsProducts as $product)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $product->name }}</td>
                            <td class="text-center font-monospace">{{ number_format($product->interest_rate, 2) }}%</td>
                            <td class="text-center font-monospace">{{ number_format($product->minimum_balance) }}</td>
                            <td class="text-center">
                                @if($product->is_compulsory)
                                    <span class="badge bg-warning bg-opacity-10 text-warning">Compulsory</span>
                                @else
                                    <span class="badge bg-light text-muted">Voluntary</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($product->is_active)
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('mfi.products.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('mfi.products.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $product->is_active ? 'danger' : 'success' }}">
                                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No savings products configured yet — a default "Standard Daily Savings" account will be used.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SHARE PRODUCTS --}}
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-chart-pie me-2"></i> Share Products</h6>
            <a href="{{ route('mfi.products.create', ['type' => 'shares']) }}" class="btn btn-sm btn-warning fw-bold shadow-sm text-white">
                <i class="fas fa-plus me-1"></i> Add Share Product
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th class="text-center">Value per Share</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shareProducts as $product)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $product->name }}</td>
                            <td class="text-center font-monospace">{{ number_format($product->share_value) }}</td>
                            <td class="text-center">
                                @if($product->is_active)
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('mfi.products.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('mfi.products.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $product->is_active ? 'danger' : 'success' }}">
                                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                No share products configured yet. You'll need one before members can buy shares.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- FIXED DEPOSIT PRODUCTS --}}
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-lock me-2"></i> Fixed Deposit Products</h6>
            <a href="{{ route('mfi.products.create', ['type' => 'fixed_deposit']) }}" class="btn btn-sm btn-info fw-bold shadow-sm text-white">
                <i class="fas fa-plus me-1"></i> Add Fixed Deposit Product
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th class="text-center">Interest Rate</th>
                            <th class="text-center">Term</th>
                            <th class="text-center">Early Withdrawal Penalty</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fixedDepositProducts as $product)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $product->name }}</td>
                            <td class="text-center font-monospace">{{ number_format($product->interest_rate, 2) }}%</td>
                            <td class="text-center">{{ $product->term_months }} months</td>
                            <td class="text-center font-monospace">{{ number_format($product->early_withdrawal_penalty_percent, 0) }}%</td>
                            <td class="text-center">
                                @if($product->is_active)
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('mfi.products.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('mfi.products.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $product->is_active ? 'danger' : 'success' }}">
                                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No fixed deposit products configured yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
