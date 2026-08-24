<?php
$currency = \App\Models\LoanManager::getCurrency();

$ratioMeta = [
    'par_30' => ['label' => 'Portfolio at Risk (30 days)', 'good' => 'lower', 'hint' => 'Share of the loan portfolio at least 31 days late. Lower is healthier; many regulators flag anything above ~5%.'],
    'par_90' => ['label' => 'Portfolio at Risk (90 days)', 'good' => 'lower', 'hint' => 'Share of the portfolio at least 91 days late — the loans closest to being written off.'],
    'reserve_adequacy' => ['label' => 'Reserve Adequacy', 'good' => 'higher', 'hint' => 'Actual Loan Loss Reserve balance vs. what today\'s classification requires. 100% means the reserve is fully funded.'],
    'npl_coverage' => ['label' => 'NPL Coverage Ratio', 'good' => 'higher', 'hint' => 'Loan Loss Reserve balance as a share of the 90+ day portfolio-at-risk balance.'],
    'liquidity' => ['label' => 'Liquidity Ratio', 'good' => 'higher', 'hint' => 'Cash + bank balances as a share of member deposits (savings + fixed deposits) — ability to meet withdrawal demand.'],
    'loans_to_deposits' => ['label' => 'Loans-to-Deposits Ratio', 'good' => 'context', 'hint' => 'Gross loan portfolio as a share of member deposits. Very high means most deposits are lent out; very low means idle capital.'],
    'equity_to_assets' => ['label' => 'Equity-to-Assets Ratio', 'good' => 'higher', 'hint' => 'Owner/member equity as a share of total assets — a simple capital-strength indicator.'],
];
?>
@extends('layouts.manager')

@section('title', 'Prudential Returns')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">Prudential Returns</h1>
            <p class="text-muted mb-0">Standard regulatory ratios as of {{ $asOf->format('d-M-Y') }}, computed from the Chart of Accounts ledger.</p>
        </div>
        <a href="{{ route('reports.prudential-returns.pdf') }}" class="btn btn-outline-secondary no-print" target="_blank">
            <i class="fas fa-file-pdf me-2"></i> Download PDF
        </a>
    </div>

    @if (!$accountsSeeded)
        <div class="alert alert-warning m-3 mb-0">
            No Chart of Accounts set up yet. Go to <a href="{{ route('chart-of-accounts.index') }}">Chart of Accounts</a> and click "Seed Standard Accounts" — these ratios are computed from the ledger, so they need it in place.
        </div>
    @endif

    <div class="card-body">
        <div class="row g-3">
            @foreach ($ratios as $key => $value)
                @php $meta = $ratioMeta[$key]; @endphp
                <div class="col-md-4 col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ $meta['label'] }}</div>
                        <div class="h3 mb-1">{{ $value === null ? 'N/A' : number_format($value, 2) . '%' }}</div>
                        <div class="small text-muted">{{ $meta['hint'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Underlying Figures</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <tbody>
                    <tr><th>Cash on Hand</th><td class="text-end">{{ $cashOnHand === null ? 'N/A' : $currency . ' ' . number_format($cashOnHand, 2) }}</td></tr>
                    <tr><th>Cash at Bank</th><td class="text-end">{{ $cashAtBank === null ? 'N/A' : $currency . ' ' . number_format($cashAtBank, 2) }}</td></tr>
                    <tr><th>Liquid Assets (Cash + Bank)</th><td class="text-end">{{ $liquidAssets === null ? 'N/A' : $currency . ' ' . number_format($liquidAssets, 2) }}</td></tr>
                    <tr><th>Gross Loan Portfolio</th><td class="text-end">{{ $loanPortfolio === null ? 'N/A' : $currency . ' ' . number_format($loanPortfolio, 2) }}</td></tr>
                    <tr><th>Loan Loss Reserve</th><td class="text-end">{{ $loanLossReserve === null ? 'N/A' : $currency . ' ' . number_format($loanLossReserve, 2) }}</td></tr>
                    <tr><th>Net Loan Portfolio</th><td class="text-end">{{ $netLoanPortfolio === null ? 'N/A' : $currency . ' ' . number_format($netLoanPortfolio, 2) }}</td></tr>
                    <tr><th>Member Savings</th><td class="text-end">{{ $memberSavings === null ? 'N/A' : $currency . ' ' . number_format($memberSavings, 2) }}</td></tr>
                    <tr><th>Member Fixed Deposits</th><td class="text-end">{{ $memberFixedDeposits === null ? 'N/A' : $currency . ' ' . number_format($memberFixedDeposits, 2) }}</td></tr>
                    <tr><th>Total Deposits</th><td class="text-end">{{ $totalDeposits === null ? 'N/A' : $currency . ' ' . number_format($totalDeposits, 2) }}</td></tr>
                    <tr><th>Total Assets</th><td class="text-end">{{ $currency }} {{ number_format($totalAssets, 2) }}</td></tr>
                    <tr><th>Total Liabilities</th><td class="text-end">{{ $currency }} {{ number_format($totalLiabilities, 2) }}</td></tr>
                    <tr><th>Total Equity</th><td class="text-end">{{ $currency }} {{ number_format($totalEquity, 2) }}</td></tr>
                    <tr><th>30+ Days Portfolio at Risk</th><td class="text-end">{{ $currency }} {{ number_format($par30Outstanding, 2) }}</td></tr>
                    <tr><th>90+ Days Portfolio at Risk</th><td class="text-end">{{ $currency }} {{ number_format($par90Outstanding, 2) }}</td></tr>
                    <tr><th>Required Provision (current classification)</th><td class="text-end">{{ $currency }} {{ number_format($totalRequiredProvision, 2) }}</td></tr>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mb-0">
            See <a href="{{ route('reports.loan-classification') }}">Loan Classification & Provisioning</a> for the per-loan breakdown behind these portfolio-at-risk figures.
        </p>
    </div>
</div>
@endsection
