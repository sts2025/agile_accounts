<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Loan Aging Analysis</title>
    <style> body { font-family: 'Helvetica', sans-serif; font-size: 9px; } .header { text-align: center; margin-bottom: 15px; } h1 { margin: 0; font-size: 16px;} table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #777; padding: 4px; text-align: left;} th { background-color: #f2f2f2; } .text-end { text-align: right; } .text-danger { color: red; }</style>
</head>
<body>
    <div class="header"><h1>Agile Accounts - Loan Aging Analysis</h1><p>Report generated on {{ now()->format('d F, Y') }}</p></div>
    <table>
        <thead><tr><th>#</th><th>Client</th><th>Guarantor</th><th>Date Given</th><th>Final Due Date</th><th>Principal</th><th>Interest</th><th>Balance</th><th>Arrears</th><th>Days Missed</th></tr></thead>
        <tbody>
            @foreach ($analyzedLoans as $index => $item)
                @php
                    $loan = $item->loan;
                    $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
                    $finalDueDate = \Carbon\Carbon::parse($loan->start_date);
                    switch ($loan->repayment_frequency) {
                        case 'Daily':   $finalDueDate->addDays($loan->term);   break;
                        case 'Weekly':  $finalDueDate->addWeeks($loan->term);  break;
                        default:        $finalDueDate->addMonths($loan->term); break;
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $loan->client->name ?? 'N/A' }}</td>
                    <td>{{ $loan->guarantors->first()->first_name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($loan->start_date)->format('d-m-Y') }}</td>
                    <td>{{ $finalDueDate->format('d-m-Y') }}</td>
                    <td class="text-end">{{ number_format($loan->principal_amount, 0) }}</td>
                    <td class="text-end">{{ number_format($totalInterest, 0) }}</td>
                    <td class="text-end">{{ number_format($item->balance, 0) }}</td>
                    <td class="text-end {{ $item->total_arrears > 0 ? 'text-danger' : '' }}">{{ number_format($item->total_arrears, 0) }}</td>
                    <td class="text-end {{ $item->days_missed > 0 ? 'text-danger' : '' }}">{{ $item->days_missed }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>