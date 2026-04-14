<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - {{ $loan->client->name }}</title>
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            font-size: 11px;
        }

        /* ADDED: Print Button Styles */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
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

    <!-- ADDED: Print Button Container (Hides when printing) -->
    <div class="no-print" style="text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <button onclick="window.print()" class="btn-print">🖨️ Print Loan Agreement</button>
    </div>

    @php
        $manager = $loan->loanManager;
        $client = $loan->client;
        $currency = $manager->currency_symbol ?? 'UGX';
        $interest = $loan->interest_amount ?? ($loan->principal_amount * ($loan->interest_rate / 100));
        $totalDue = $loan->principal_amount + $interest + ($loan->processing_fee ?? 0);
    @endphp

    <div class="header-section">
        <h1 class="company-name">{{ $manager->company_name ?? 'STREAMLINE TECH SOLUTION' }}</h1>
        <div class="company-details">
            {{ $manager->company_address ?? $manager->address ?? 'Company Address' }} | Phone: {{ $manager->company_phone ?? $manager->phone_number ?? 'N/A' }} | Email: {{ Auth::user()->email }}
        </div>
    </div>

    <div class="document-title">LOAN AGREEMENT</div>

    <div class="section-header">BORROWER DETAILS</div>
    <table class="layout-table">
        <tr>
            <td class="details-col">
                <p><span class="label">Name:</span> {{ $client->name }}</p>
                <p><span class="label">Phone:</span> {{ $client->phone_number }}</p>
                <p><span class="label">National ID (NIN):</span> {{ $client->national_id ?? '_________________' }}</p>
                <p><span class="label">Date of Birth:</span> {{ $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('d M, Y') : '_________________' }}</p>
                <p><span class="label">Address:</span> {{ $client->address }}</p>
                <p><span class="label">Business/Occupation:</span> {{ $client->business_occupation ?? 'N/A' }}</p>
            </td>
            <td class="photo-col">
                <div class="photo-box">Affix Borrower<br>Passport Photo</div>
            </td>
        </tr>
    </table>

    <div class="section-header">LOAN DETAILS</div>
    <p><span class="label">Loan Amount (Principal):</span> {{ $currency }} {{ number_format($loan->principal_amount) }}</p>
    <p><span class="label">Processing Fee (One-time):</span> {{ $currency }} {{ number_format($loan->processing_fee ?? 0) }}</p>
    <p><span class="label">Interest Amount:</span> {{ $currency }} {{ number_format($interest) }} ({{ $loan->interest_rate }}% Flat Rate)</p>
    <p><span class="label">Total Amount to be Repaid:</span> {{ $currency }} {{ number_format($totalDue) }}</p>
    <p><span class="label">Term:</span> {{ $loan->term ?? '____' }} {{ $loan->repayment_frequency ?? 'Months' }}</p>
    <p><span class="label">Disbursement Date:</span> {{ \Carbon\Carbon::parse($loan->start_date)->format('F d, Y') }}</p>

    <div class="section-header">GUARANTOR DETAILS</div>
    <table class="layout-table">
        <tr>
            <td class="details-col">
                @if($loan->guarantors && $loan->guarantors->count() > 0)
                    @php $g = $loan->guarantors->first(); @endphp
                    <p><span class="label">Name:</span> {{ $g->first_name }} {{ $g->last_name }}</p>
                    <p><span class="label">Phone:</span> {{ $g->phone_number }}</p>
                    <p><span class="label">Address:</span> {{ $g->address }}</p>
                    <p><span class="label">Occupation:</span> {{ $g->occupation ?? 'N/A' }}</p>
                    <p><span class="label">Relationship:</span> {{ $g->relationship_to_borrower ?? 'N/A' }}</p>
                @else
                    <p><em>No guarantor details recorded for this loan.</em></p>
                @endif
            </td>
            <td class="photo-col">
                <div class="photo-box">Affix Guarantor<br>Passport Photo</div>
            </td>
        </tr>
    </table>

    <div class="section-header">COLLATERAL DETAILS</div>
    @if($loan->collaterals && $loan->collaterals->count() > 0)
        @foreach($loan->collaterals as $c)
            <p><span class="label">Type:</span> {{ $c->collateral_type ?? 'N/A' }}</p>
            <p><span class="label">Description:</span> {{ $c->name ?? $c->description }}</p>
            <p><span class="label">Valuation:</span> {{ $currency }} {{ number_format($c->valuation_amount ?? $c->value ?? 0) }}</p>
            @if(!$loop->last)<hr style="border:0; border-top: 1px dashed #ccc; margin: 10px 0;">@endif
        @endforeach
    @else
        <p><em>No collateral details recorded for this loan.</em></p>
    @endif

    <div class="section-header">TERMS AND CONDITIONS</div>
    <p style="font-size: 11px; text-align: justify; margin-bottom: 5px;"><strong>1. AGREEMENT:</strong> This Loan Agreement ("Agreement") is made on {{ \Carbon\Carbon::parse($loan->start_date)->format('F d, Y') }} by and between {{ $manager->company_name ?? 'the Lender' }} ("Lender") and <strong>{{ $client->name }}</strong> ("Borrower").</p>
    <p style="font-size: 11px; text-align: justify; margin-bottom: 5px;"><strong>2. REPAYMENT:</strong> The Borrower agrees to repay the Total Repayable Amount of <strong>{{ $currency }} {{ number_format($totalDue) }}</strong> in installments as per the agreed-upon schedule.</p>
    <p style="font-size: 11px; text-align: justify; margin-bottom: 5px;"><strong>3. DEFAULT:</strong> Failure to make a payment for more than seven (7) days after the due date will be considered a default. In the event of default, the Lender has the right to demand immediate full payment of the outstanding balance and may seize any collateral listed.</p>
    <p style="font-size: 11px; text-align: justify; margin-bottom: 5px;"><strong>4. GOVERNING LAW:</strong> This Agreement shall be governed by and construed in accordance with the laws of Uganda. The borrower confirms the authenticity of the National ID and details provided herein.</p>

    <p style="font-size: 10px; margin-top: 30px; text-align: center;">By signing below, all parties agree to the terms and conditions of this loan agreement.</p>

    <table style="width: 100%; margin-top: 40px; text-align: center;">
        <tr>
            <td style="width: 33%; padding: 0 15px;">
                <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 11px;">Borrower's Signature</div>
                <div style="font-size: 10px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
            <td style="width: 33%; padding: 0 15px;">
                <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 11px;">Guarantor's Signature</div>
                <div style="font-size: 10px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
            <td style="width: 33%; padding: 0 15px;">
                <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 11px;">Lender / Manager</div>
                <div style="font-size: 10px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
        </tr>
    </table>
</body>
</html>