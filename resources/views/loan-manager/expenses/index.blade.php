<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')

@section('title', 'My Expenses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">My Expenses</h1>
        <a href="#" class="btn btn-primary no-print" onclick="window.print();">Print Report</a>
    </div>

    {{-- Date Filter Card --}}
    <div class="card shadow mb-4 no-print">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Expenses</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.index') }}">
                <div class="row">
                    <div class="col-md-5">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-5">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100">Filter Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Expenses Table Card --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th class="text-end">Amount ({{ $currency }})</th> {{-- FIX 1: Dynamic Currency Header --}}
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalExpenses = 0;
                        @endphp
                        @forelse ($expenses as $expense)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</td>
                                
                                {{-- FIX 2: Display Category Name (assuming a 'category' relationship exists) --}}
                                <td>{{ $expense->category->name ?? 'N/A' }}</td> 
                                
                                <td class="text-end">{{ number_format($expense->amount, 0) }}</td>
                            </tr>
                            @php
                                $totalExpenses += $expense->amount;
                            @endphp
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No expenses found for the selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Total Expenses</td>
                            <td class="text-end">{{ $currency }} {{ number_format($totalExpenses, 0) }}</td> {{-- FIX 3: Dynamic Currency Footer --}}
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection