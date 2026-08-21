<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Savings Passbook - {{ $account->client->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header-section {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .company-details {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }
        .company-logo {
            max-height: 80px;
            max-width: 250px;
            display: block;
            margin: 0 auto 10px auto;
        }
        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }
        .section-header {
            font-size: 14px;
            font-weight: bold;
            background-color: #f4f4f4;
            padding: 8px;
            margin-top: 25px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        p { margin: 4px 0; font-size: 13px; }
        .label { font-weight: bold; display: inline-block; width: 140px; }

        table.ledger { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.ledger th, table.ledger td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            font-size: 11px;
        }
        table.ledger th { background-color: #f4f4f4; text-align: left; }
        table.ledger td.num { text-align: right; font-family: 'Courier New', monospace; }
        table.ledger tfoot td { font-weight: bold; background-color: #f9f9f9; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; max-width: 100%; }
        }
        .btn-print {
            background-color: #0d47a1;
            color: #fff;
            padding: 10px 20px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <button onclick="window.print()" class="btn-print">🖨️ Print Passbook</button>
    </div>

    @php
        $manager = Auth::user()->getCompany() ?? Auth::user()->loanManager ?? $account->client->loanManager;
        $currency = $manager->currency_symbol ?? 'UGX';

        $companyName = !empty($manager->company_name) ? $manager->company_name : (optional($manager->user)->name ?? 'STREAMLINE TECH SOLUTION');
        $companyPhone = !empty($manager->company_phone) ? $manager->company_phone : (!empty($manager->phone_number) ? $manager->phone_number : 'N/A');
        $companyAddress = !empty($manager->company_address) ? $manager->company_address : (!empty($manager->address) ? $manager->address : 'N/A');
        $companyEmail = !empty($manager->company_email) ? $manager->company_email : Auth::user()->email;
        $companyLogo = !empty($manager->company_logo) ? $manager->company_logo : (!empty($manager->company_logo_path) ? $manager->company_logo_path : null);
    @endphp

    <div class="header-section">
        @if($companyLogo)
            <img src="{{ asset('storage/' . $companyLogo) }}" alt="Company Logo" class="company-logo">
        @endif
        <h1 class="company-name">{{ $companyName }}</h1>
        <div class="company-details">
            {{ $companyAddress }} | Phone: {{ $companyPhone }} | Email: {{ $companyEmail }}
        </div>
    </div>

    <div class="document-title">SAVINGS PASSBOOK</div>

    <div class="section-header">ACCOUNT HOLDER</div>
    <p><span class="label">Name:</span> {{ $account->client->name }}</p>
    <p><span class="label">Phone:</span> {{ $account->client->phone_number }}</p>
    <p><span class="label">Account Number:</span> {{ $account->account_number }}</p>
    <p><span class="label">Account Status:</span> {{ ucfirst($account->status) }}</p>
    <p><span class="label">Printed On:</span> {{ \Carbon\Carbon::now()->format('d M, Y H:i') }}</p>

    <div class="section-header">TRANSACTION HISTORY</div>
    <table class="ledger">
        <thead>
            <tr>
                <th>Date</th>
                <th>Narration</th>
                <th>Ref</th>
                <th class="num">Withdrawal</th>
                <th class="num">Deposit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $running = 0; @endphp
            @forelse($account->transactions as $tx)
                @php
                    $isWithdrawal = $tx->transaction_type === 'withdrawal';
                    $running += $isWithdrawal ? -$tx->amount : $tx->amount;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d M, Y') }}</td>
                    <td>{{ $tx->narration ?? ucfirst($tx->transaction_type) }}</td>
                    <td>{{ $tx->reference_number ?? '—' }}</td>
                    <td class="num">{{ $isWithdrawal ? number_format($tx->amount, 2) : '' }}</td>
                    <td class="num">{{ !$isWithdrawal ? number_format($tx->amount, 2) : '' }}</td>
                    <td class="num">{{ number_format($running, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No transactions recorded yet.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">Closing Balance ({{ $currency }})</td>
                <td class="num">{{ number_format($account->balance, 2) }}</td>
            </tr>
            @if($account->lien_amount > 0)
            <tr>
                <td colspan="5" style="text-align:right;">Locked as Loan Collateral</td>
                <td class="num">{{ number_format($account->lien_amount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:right;">Available Balance</td>
                <td class="num">{{ number_format($account->balance - $account->lien_amount, 2) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <p style="margin-top: 30px; font-size: 11px; color: #777;">
        This is a system-generated statement of the savings account held with {{ $companyName }}.
    </p>

</body>
</html>
