@extends('layouts.manager')

@section('title', 'Preview Interest Posting')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-eye text-primary me-2"></i> Preview Interest Posting</h1>
        <a href="{{ route('mfi.end-of-period.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Start Over
        </a>
    </div>

    <div class="alert alert-info shadow-sm border-start border-info border-4">
        <strong>As of:</strong> {{ \Carbon\Carbon::parse($asOfDate)->format('d M, Y') }}
        &middot; <strong>{{ $rows->count() }}</strong> account(s) have interest due.
        Nothing has been posted yet — review below, then confirm.
    </div>

    @if($rows->isEmpty())
        <div class="alert alert-warning shadow-sm border-start border-warning border-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No interest is due as of this date — every account is already up to date, has a zero balance,
            or is on a product with no interest rate configured.
        </div>
    @else
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Interest Due</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Client</th>
                            <th>Account</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Days</th>
                            <th class="text-end pe-4">Interest</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $row->client_name }}</td>
                            <td class="text-muted small font-monospace">{{ $row->account_number }}</td>
                            <td class="text-end font-monospace">{{ number_format($row->balance) }}</td>
                            <td class="text-end">{{ number_format($row->rate, 2) }}%</td>
                            <td class="text-end">{{ $row->days }}</td>
                            <td class="text-end pe-4 font-monospace fw-bold text-success">{{ number_format($row->interest, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end ps-4">Total Interest to Post</td>
                            <td class="text-end pe-4 font-monospace">{{ number_format($totalInterest, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <form action="{{ route('mfi.end-of-period.post') }}" method="POST" onsubmit="return confirm('This will credit interest to every account listed above and cannot be undone. Continue?');">
        @csrf
        <input type="hidden" name="as_of_date" value="{{ $asOfDate }}">
        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
            <i class="fas fa-check-circle me-2"></i> Confirm &amp; Post Interest
        </button>
        <a href="{{ route('mfi.end-of-period.index') }}" class="btn btn-light fw-bold">Cancel</a>
    </form>
    @endif
</div>
@endsection
