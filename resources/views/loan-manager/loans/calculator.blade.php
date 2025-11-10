<?php
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')

@section('title', 'Repayment Calculator')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4 text-gray-800">Repayment Schedule Calculator</h1>

    {{-- Calculator Form --}}
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Enter Loan Details</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('loans.showCalculator') }}">
                <input type="hidden" name="calculate" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="principal_amount" class="form-label">Principal Amount ({{ $currency }})</label>
                        <input type="number" step="100" name="principal_amount" id="principal_amount" class="form-control" value="{{ $principal }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                        <input type="number" step="0.1" name="interest_rate" id="interest_rate" class="form-control" value="{{ $interestRate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="term" class="form-label">Loan Term (Periods)</label>
                        <input type="number" name="term" id="term" class="form-control" value="{{ $term }}" required min="1">
                    </div>
                    <div class="col-md-3">
                        <label for="repayment_frequency" class="form-label">Frequency</label>
                        <select name="repayment_frequency" id="repayment_frequency" class="form-control" required>
                            <option value="Daily" {{ $frequency == 'Daily' ? 'selected' : '' }}>Daily</option>
                            <option value="Weekly" {{ $frequency == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="Monthly" {{ $frequency == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3"><i class="fas fa-calculator me-2"></i> Calculate Schedule</button>
            </form>
        </div>
    </div>

    {{-- Schedule Output --}}
    @if ($calculationPerformed)

    <div class="row text-center mb-3">
        <div class="col-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Principal</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $currency }} {{ number_format($principal, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Interest</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $currency }} {{ number_format($totalInterest, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Repayable</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $currency }} {{ number_format($totalRepayable, 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow mb-4 mt-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Projected Schedule</h6>
        </div>
        <div class="card-body">
            @if (count($schedule) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Due Date</th>
                                <th class="text-end">Payment Amount ({{ $currency }})</th>
                                <th class="text-end">Principal ({{ $currency }})</th>
                                <th class="text-end">Interest ({{ $currency }})</th>
                                <th class="text-end">Remaining Balance ({{ $currency }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedule as $item)
                            <tr>
                                <td>{{ $item['period'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($item['due_date'])->format('d M, Y') }}</td>
                                <td class="text-end">{{ number_format($item['payment_amount'], 0) }}</td>
                                <td class="text-end">{{ number_format($item['principal'], 0) }}</td>
                                <td class="text-end">{{ number_format($item['interest'], 0) }}</td>
                                <td class="text-end">{{ number_format($item['balance'], 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No schedule generated. Please check your principal and term values.</p>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection