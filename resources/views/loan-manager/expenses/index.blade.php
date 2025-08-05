@extends('layouts.manager')
@section('title', 'All Expenses')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>All Expenses</h1>
        <a href="{{ route('expenses.pdf') }}" class="btn btn-primary" target="_blank">Print Report</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="text-end">Amount (UGX)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No expenses have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection