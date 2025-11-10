<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')
@section('title', 'Profit & Loss Report')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4">Profit & Loss Report</h1>
    <h5>Period: {{ \Carbon\Carbon::parse($startDate)->format('d M, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M, Y') }}</h5>

    <div class="row mt-4">
        {{-- Income Section --}}
        <div class="col-md-6 mb-4">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="mb-0 text-primary">Income</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            @forelse($incomeAccounts as $account)
                                @if($account->period_total != 0)
                                    <tr>
                                        <td class="ps-3">{{ $account->name }}</td>
                                        <td class="text-end pe-3">{{ number_format($account->period_total, 0) }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="2" class="text-center text-muted p-3">No income recorded for this period.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Total Income</td>
                                <td class="text-end pe-3">{{ $currency }} {{ number_format($totalIncome, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Expenses Section --}}
        <div class="col-md-6 mb-4">
            <div class="card border-start border-danger border-4 h-100">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="mb-0 text-danger">Expenses</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            @forelse($expenseAccounts as $account)
                                @if($account->period_total != 0)
                                    <tr>
                                        <td class="ps-3">{{ $account->name }}</td>
                                        <td class="text-end pe-3">{{ number_format($account->period_total, 0) }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="2" class="text-center text-muted p-3">No expenses recorded for this period.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Total Expenses</td>
                                <td class="text-end pe-3">{{ $currency }} {{ number_format($totalExpenses, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Net Profit Section --}}
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="card @if($netProfit >= 0) bg-success @else bg-danger @endif text-white shadow">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0">Net Profit</h4>
                    <h4 class="mb-0">{{ $currency }} {{ number_format($netProfit, 0) }}</h4>
                </div>
            </div>
            <a href="https://wa.me/?text={{ $whatsappMessage }}" target="_blank" class="btn btn-success mt-3 no-print">
                <i class="bi bi-whatsapp me-2"></i>Share via WhatsApp
            </a>
        </div>
    </div>
</div>
@endsection