<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1>Trial Balance</h1>
                <p class="text-muted">As of {{ now()->format('F d, Y') }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Admin Dashboard</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
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
                    <tfoot class="table-group-divider">
                        <tr class="fw-bold">
                            <td>Totals</td>
                            <td class="text-end">{{ number_format($grandTotalDebits, 2) }}</td>
                            <td class="text-end">{{ number_format($grandTotalCredits, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                @if(number_format($grandTotalDebits, 2) == number_format($grandTotalCredits, 2))
                    <div class="alert alert-success mt-3">
                        The totals match. Your ledger is balanced!
                    </div>
                @else
                    <div class="alert alert-danger mt-3">
                        Warning: The totals do not match. Your ledger is out of balance.
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>