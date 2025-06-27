<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Statement</title>
    <style>/* ... Use the same styles as the trial balance PDF ... */</style>
</head>
<body>
    <div class="header">
        <h1>Agile Accounts</h1>
        <h2>Profit & Loss Statement</h2>
        <p>For the period: {{ \Carbon\Carbon::parse($startDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M, Y') }}</p>
    </div>
    <h3>Income</h3>
    <table>
        <tbody>
            @foreach ($incomeAccounts as $account)
                @if($account->period_total > 0)
                <tr><td>{{ $account->name }}</td><td class="text-end">UGX {{ number_format($account->period_total, 2) }}</td></tr>
                @endif
            @endforeach
            <tr class="footer-totals"><td>Total Income</td><td class="text-end">UGX {{ number_format($totalIncome, 2) }}</td></tr>
        </tbody>
    </table>
    <h3 style="margin-top: 20px;">Expenses</h3>
     <table>
        <tbody>
            @forelse ($expenseAccounts as $account)
                @if($account->period_total > 0)
                <tr><td>{{ $account->name }}</td><td class="text-end">(UGX {{ number_format($account->period_total, 2) }})</td></tr>
                @endif
            @empty
                <tr><td colspan="2">No expenses recorded.</td></tr>
            @endforelse
            <tr class="footer-totals"><td>Total Expenses</td><td class="text-end">(UGX {{ number_format($totalExpenses, 2) }})</td></tr>
        </tbody>
    </table>
    <hr>
    <div style="font-size: 16px; font-weight: bold; margin-top: 20px;">
        Net Profit / Loss: UGX {{ number_format($netProfit, 2) }}
    </div>
</body>
</html>