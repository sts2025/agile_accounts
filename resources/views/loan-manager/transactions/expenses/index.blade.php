<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>
@extends('layouts.manager')
@section('title', 'My Expenses')

@section('content')
<div class="card" id="printable-area">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h1>My Expenses</h1>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('expenses.index') }}" class="mb-4 p-3 bg-light border rounded no-print">
            <div class="row align-items-end">
                <div class="col-md-4"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ $startDate }}"></div>
                <div class="col-md-4"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ $endDate }}"></div>
                <div class="col-md-4"><button type="submit" class="btn btn-secondary w-100">Filter Report</button></div>
            </div>
        </form>

        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 25%;">Category</th>
                    <th style="width: 40%;">Description</th> {{-- Added missing Description column --}}
                    <th class="text-end" style="width: 20%;">Amount ({{ $currency }})</th> {{-- FIX 1: Dynamic Currency --}}
                </tr>
            </thead>
            <tbody>
                @php
                    $totalExpenses = 0;
                @endphp
                @forelse($expenses as $expense)
                    @php
                        $totalExpenses += $expense->amount;
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</td>
                        <td>{{ $expense->category->name ?? 'Uncategorized' }}</td>
                        <td>{{ $expense->description ?? 'N/A' }}</td> {{-- Display Description --}}
                        <td class="text-end text-danger">{{ number_format($expense->amount, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No expenses found for the selected period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-group-divider fw-bold">
                <tr class="table-danger">
                    <td colspan="3" class="text-end">TOTAL EXPENSES:</td> {{-- Colspan changed to 3 due to new Description column --}}
                    <td class="text-end">{{ $currency }} {{ number_format($totalExpenses, 0) }}</td> {{-- FIX 2: Dynamic Currency and total --}}
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none; }
    }
</style>
@endsection