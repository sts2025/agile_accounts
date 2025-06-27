<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trial Balance</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .footer-totals { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Agile Accounts</h1>
        <h2>Trial Balance</h2>
        <p>As of {{ now()->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            @extends('layouts.app')
@section('title', 'Trial Balance Report')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>My Trial Balance</h1>
            <p class="text-muted">As of {{ now()->format('F d, Y') }}</p>
        </div>
        <div>
            <a href="{{ route('manager.reports.trial-balance.pdf') }}" class="btn btn-primary" target="_blank">Download PDF</a>
            <a href="whatsapp://send?text={{ $whatsappMessage }}" class="btn btn-success">Share on WhatsApp</a>
        </div>
        </div>

    @endsection
            <tr>
                <th>Account</th>
                <th class="text-end">Debit (UGX)</th>
                <th class="text-end">Credit (UGX)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($accounts as $account)
                @if ($account->total_debits > 0 || $account->total_credits > 0)
                    <tr>
                        <td>{{ $account->name }}</td>
                        <td class="text-end">{{ number_format($account->total_debits, 2) }}</td>
                        <td class="text-end">{{ number_format($account->total_credits, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="footer-totals">
                <td>Totals</td>
                <td class="text-end">{{ number_format($grandTotalDebits, 2) }}</td>
                <td class="text-end">{{ number_format($grandTotalCredits, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>