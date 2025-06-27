<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #{{ $payment->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 14px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .details-table th { background-color: #f2f2f2; }
        .total-amount { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Agile Accounts</h1>
            <p>Payment Receipt</p>
            <p><strong>Receipt No:</strong> {{ $payment->receipt_number ?? $payment->id }}</p>
            <a href="whatsapp://send?text={{ $whatsappMessage }}" style="display: inline-block; padding: 10px 15px; background-color: #25D366; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;">
                Share on WhatsApp
            </a>
        </div>

        <table class="details-table">
            <tr>
                <th>Payment Date</th>
                <td>{{ $payment->payment_date->format('F d, Y') }}</td>
            </tr>
            <tr>
                <th>Client Name</th>
                <td>{{ $payment->loan->client->name }}</td>
            </tr>
            <tr>
                <th>Loan ID</th>
                <td>Loan #{{ $payment->loan_id }}</td>
            </tr>
            <tr>
                <th>Payment Method</th>
                <td>{{ $payment->payment_method }}</td>
            </tr>
             <tr>
                <th>Amount Paid</th>
                <td><strong>UGX {{ number_format($payment->amount_paid, 2) }}</strong></td>
            </tr>
            @if($payment->notes)
            <tr>
                <th>Notes</th>
                <td>{{ $payment->notes }}</td>
            </tr>
            @endif
        </table>

        <div class="total-amount">
            Total Paid: UGX {{ number_format($payment->amount_paid, 2) }}
        </div>

        <div class="footer">
            <p>Thank you for your payment!</p>
            <p>Generated on: {{ now()->format('F d, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>