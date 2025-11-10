<!DOCTYPE html>
<html>
<head>
    <title>Expenses Report</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Expenses Report</h1>
    <h2>For: {{ $managerName }}</h2>
    <p>Report Generated On: {{ now()->format('F d, Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th class="text-end">Amount (UGX)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') }}</td>
                    <td>{{ $expense->category->name }}</td>
                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-end">Total Expenses</td>
                <td class="text-end">UGX {{ number_format($totalExpenses, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>