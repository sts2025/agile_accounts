<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1>Profit & Loss Statement</h1>
                <p class="text-muted">For the period from {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Admin Dashboard</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.profit-and-loss') }}" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-auto">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-auto mt-4">
                        <button type="submit" class="btn btn-primary">Run Report</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Income</h4>
                <table class="table">
                    <tbody>
                        @foreach ($incomeAccounts as $account)
                            @if($account->period_total > 0)
                            <tr>
                                <td>{{ $account->name }}</td>
                                <td class="text-end">UGX {{ number_format($account->period_total, 2) }}</td>
                            </tr>
                            @endif
                        @endforeach
                        <tr class="table-light fw-bold">
                            <td>Total Income</td>
                            <td class="text-end">UGX {{ number_format($totalIncome, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <h4 class="mt-4 mb-3">Expenses</h4>
                <table class="table">
                    <tbody>
                        @forelse ($expenseAccounts as $account)
                             @if($account->period_total > 0)
                            <tr>
                                <td>{{ $account->name }}</td>
                                <td class="text-end">(UGX {{ number_format($account->period_total, 2) }})</td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="2">No expenses recorded for this period.</td>
                            </tr>
                        @endforelse
                        <tr class="table-light fw-bold">
                            <td>Total Expenses</td>
                            <td class="text-end">(UGX {{ number_format($totalExpenses, 2) }})</td>
                        </tr>
                    </tbody>
                </table>

                <hr>
                <div class="d-flex justify-content-between fs-4 fw-bold mt-3">
                    <span>Net Profit / Loss</span>
                    <span>UGX {{ number_format($netProfit, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>