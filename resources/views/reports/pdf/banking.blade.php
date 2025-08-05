<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Bank Deposits Report</title><style>/* ... styles ... */</style></head>
<body>
    <div class="header"><h1>Bank Deposits Report</h1></div>
    <table>
        <thead><tr><th>Date</th><th>Reference No.</th><th class="text-end">Amount (UGX)</th></tr></thead>
        <tbody>
            @foreach ($deposits as $deposit)
            <tr>
                <td>{{ \Carbon\Carbon::parse($deposit->deposit_date)->format('d-m-Y') }}</td>
                <td>{{ $deposit->reference_number ?? 'N/A' }}</td>
                <td class="text-end">{{ number_format($deposit->amount, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>