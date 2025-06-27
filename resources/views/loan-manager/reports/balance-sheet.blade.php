@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>My Balance Sheet</h1>
            <p class="text-muted">As of {{ $asOfDate->format('F d, Y') }}</p>
        </div>
        <div>
            <a href="{{ route('manager.reports.balance-sheet.pdf', ['as_of_date' => $asOfDate->toDateString()]) }}" class="btn btn-primary" target="_blank">Download PDF</a>
            <a href="whatsapp://send?text={{ $whatsappMessage }}" class="btn btn-success">Share on WhatsApp</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('manager.reports.balance-sheet') }}" class="row g-3 align-items-center">
                <div class="col-auto"><label>Show Report as of Date:</label><input type="date" class="form-control" name="as_of_date" value="{{ $asOfDate->toDateString() }}"></div>
                <div class="col-auto mt-4"><button type="submit" class="btn btn-primary">Run Report</button></div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header bg-success text-white"><h4>Assets</h4></div>
                <div class="card-body">
                    <table class="table">
                        <tbody>@foreach ($assets as $account) @if($account->balance != 0)<tr><td>{{ $account->name }}</td><td class="text-end">UGX {{ number_format($account->balance, 2) }}</td></tr>@endif @endforeach</tbody>
                        <tfoot class="table-group-divider"><tr class="fw-bold fs-5"><td>Total Assets</td><td class="text-end">UGX {{ number_format($totalAssets, 2) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header bg-warning"><h4>Liabilities and Equity</h4></div>
                <div class="card-body">
                    <h5>Liabilities</h5>
                    <table class="table">
                        <tbody>@forelse ($liabilities as $account) @if($account->balance != 0)<tr><td>{{ $account->name }}</td><td class="text-end">UGX {{ number_format($account->balance, 2) }}</td></tr>@endif @empty <tr><td>No liabilities recorded.</td><td></td></tr> @endforelse</tbody>
                    </table>
                    <h5 class="mt-3">Equity</h5>
                    <table class="table">
                         <tbody>@foreach ($equityAccounts as $account) @if($account->balance != 0)<tr><td>{{ $account->name }}</td><td class="text-end">UGX {{ number_format($account->balance, 2) }}</td></tr>@endif @endforeach<tr><td>Net Income</td><td class="text-end">UGX {{ number_format($netIncome, 2) }}</td></tr></tbody>
                        <tfoot class="table-group-divider"><tr class="fw-bold fs-5"><td>Total Liabilities & Equity</td><td class="text-end">UGX {{ number_format($totalLiabilitiesAndEquity, 2) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection