<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Cash Transfers Report</title><style>/* ... styles ... */</style></head>
<body>
    <div class="header"><h1>Cash Transfers Report</h1></div>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount (UGX)</th></tr></thead>
        <tbody>
            @foreach ($transfers as $transfer)
            <tr>
                <td>{{ \Carbon\Carbon::parse($transfer->transaction_date)->format('d-m-Y') }}</td>
                <td>{{ ucfirst($transfer->type) }}</td>
                <td>{{ $transfer->description }}</td>
                <td class="text-end">{{ number_format($transfer->amount, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>