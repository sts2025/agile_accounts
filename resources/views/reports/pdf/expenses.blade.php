<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Expenses Report</title>
    <style>/* ... Use the same styles as your other PDF reports ... */</style>
</head>
<body>
    <div class="header"><h1>Expenses Report</h1><p>Generated on {{ now()->format('d F, Y') }}</p></div>
    <table>
        <thead><tr><th>Date</th><th>Description</th><th class="text-end">Amount (UGX)</th></tr></thead>
        <tbody>
            @foreach ($expenses as $expense)
            <tr>
                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d-m-Y') }}</td>
                <td>{{ $expense->description }}</td>
                <td class="text-end">{{ number_format($expense->amount, 0) }}</td>
            </tr>
            @endforeach
            <tr class="footer-totals">
                <td colspan="2">Total Expenses</td>
                <td class="text-end">{{ number_format($expenses->sum('amount'), 0) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>