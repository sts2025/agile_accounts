<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Daily Report</title>
    <style>/* ... Use the same styles as your other PDF reports ... */</style>
</head>
<body>
    <div class="header">
        <h1>Agile Accounts - Daily Transaction Report</h1>
        <p>For Date: {{ \Carbon\Carbon::parse($reportDate)->format('F d, Y') }}</p>
    </div>

    <h3>Loans Given Out</h3>
    <table>
        <thead><tr><th>Client Name</th><th class="text-end">Principal</th><th class="text-end">Fee</th></tr></thead>
        <tbody>
            @forelse($loansGiven as $loan)
                <tr><td>{{ $loan->client->name }}</td><td class="text-end">{{ number_format($loan->principal_amount, 0) }}</td><td class="text-end">{{ number_format($loan->processing_fee, 0) }}</td></tr>
            @empty
                <tr><td colspan="3">None</td></tr>
            @endforelse
        </tbody>
        <tfoot class="footer-totals">
            <tr><td>Total Given Out</td><td class="text-end">{{ number_format($loansGiven->sum('principal_amount'), 0) }}</td><td class="text-end">{{ number_format($loansGiven->sum('processing_fee'), 0) }}</td></tr>
        </tfoot>
    </table>

    <h3 style="margin-top: 20px;">Payments Received</h3>
    <table>
        <thead><tr><th>Client Name</th><th>Receipt #</th><th class="text-end">Amount Paid</th></tr></thead>
        <tbody>
            @forelse($paymentsReceived as $payment)
                <tr><td>{{ $payment->loan->client->name }}</td><td>{{ $payment->receipt_number }}</td><td class="text-end">{{ number_format($payment->amount_paid, 0) }}</td></tr>
            @empty
                <tr><td colspan="3">None</td></tr>
            @endforelse
        </tbody>
        <tfoot class="footer-totals">
            <tr><td colspan="2">Total Received</td><td class="text-end">{{ number_format($paymentsReceived->sum('amount_paid'), 0) }}</td></tr>
        </tfoot>
    </table>
</body>
</html>