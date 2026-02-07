@extends('layouts.app')

@section('title', 'Payment History')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">All Payments</h1>
        <div class="btn-group">
            <a href="{{ route('payments.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Record New Payment
            </a>
            <button onclick="window.print()" class="btn btn-secondary shadow-sm">
                <i class="fas fa-print fa-sm text-white-50"></i> Print Report
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Payments Table --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Transaction History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Date</th>
                            <th>Receipt #</th>
                            <th>Client / Loan</th>
                            <th class="text-right">Amount</th>
                            <th>Method</th>
                            <th class="text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
                            <td class="font-weight-bold text-secondary">
                                {{ $payment->receipt_number ?? 'N/A' }}
                            </td>
                            <td>
                                @if($payment->loan && $payment->loan->client)
                                    <a href="{{ route('loans.show', $payment->loan_id) }}" class="font-weight-bold text-primary">
                                        {{ $payment->loan->client->name }}
                                    </a>
                                    <div class="small text-muted">
                                        Ref: {{ $payment->loan->reference_id ?? $payment->loan_id }}
                                    </div>
                                @else
                                    <span class="text-danger">Unknown Loan</span>
                                @endif
                            </td>
                            <td class="text-right font-weight-bold text-success">
                                {{ number_format($payment->amount_paid) }}
                            </td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td class="text-center no-print">
                                <div class="btn-group">
                                    {{-- 1. VIEW RECEIPT --}}
                                    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-sm btn-info" target="_blank" title="Print Receipt">
                                        <i class="fas fa-print"></i>
                                    </a>

                                    {{-- 2. EDIT BUTTON (This is what you need) --}}
                                    <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-sm btn-primary" title="Edit Payment">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- 3. DELETE BUTTON (This is what you need) --}}
                                    <form action="{{ route('payments.destroy', $payment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment? The loan balance will increase.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Payment">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-receipt fa-3x mb-3 text-gray-300"></i><br>
                                No payments found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    #wrapper #content-wrapper { margin: 0; padding: 0; }
}
</style>
@endsection