@extends('layouts.manager')

@section('title', 'Chart of Accounts')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-book text-primary me-2"></i> Chart of Accounts</h1>
        <div class="d-flex gap-2">
            @unless($hasAny)
                <form action="{{ route('chart-of-accounts.seed-defaults') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary fw-bold shadow-sm">
                        <i class="fas fa-magic me-1"></i> Load Standard SACCO Accounts
                    </button>
                </form>
            @endunless
            <a href="{{ route('chart-of-accounts.create') }}" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Add Account
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

    @if(!$hasAny)
        <div class="alert alert-info shadow-sm border-0">
            You don't have any accounts set up yet. Click <strong>Load Standard SACCO Accounts</strong> above for a
            sensible starting chart (Cash, Bank, Loan Portfolio, Member Savings, Share Capital, Interest Income,
            etc.), or add your own from scratch.
        </div>
    @endif

    @php
        $typeLabels = [
            'asset' => ['label' => 'Assets', 'icon' => 'fa-coins', 'color' => 'primary'],
            'liability' => ['label' => 'Liabilities', 'icon' => 'fa-hand-holding-usd', 'color' => 'danger'],
            'equity' => ['label' => 'Equity', 'icon' => 'fa-balance-scale', 'color' => 'success'],
            'income' => ['label' => 'Income', 'icon' => 'fa-arrow-up', 'color' => 'success'],
            'expense' => ['label' => 'Expenses', 'icon' => 'fa-arrow-down', 'color' => 'warning'],
        ];
    @endphp

    @foreach($typeLabels as $type => $meta)
        @if($accounts->has($type))
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-{{ $meta['color'] }}"><i class="fas {{ $meta['icon'] }} me-2"></i> {{ $meta['label'] }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4" style="width:100px;">Code</th>
                                    <th>Name</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts[$type] as $account)
                                    <tr>
                                        <td class="ps-4 font-monospace">{{ $account->code }}</td>
                                        <td class="fw-bold text-dark">
                                            {{ $account->name }}
                                            @if($account->is_system)
                                                <span class="badge bg-light text-muted border ms-1">standard</span>
                                            @endif
                                            @if($account->bank_name)
                                                <br><span class="text-muted small fw-normal">{{ $account->bank_name }}{{ $account->external_account_number ? ' — ' . $account->external_account_number : '' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($account->is_active)
                                                <span class="badge bg-success px-3 py-1 rounded-pill">Active</span>
                                            @else
                                                <span class="badge bg-secondary px-3 py-1 rounded-pill">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('chart-of-accounts.edit', $account->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('chart-of-accounts.toggle', $account->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $account->is_active ? 'danger' : 'success' }}">
                                                    {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
