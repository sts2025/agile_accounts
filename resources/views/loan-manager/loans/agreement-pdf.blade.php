<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - {{ $loan->client->name }}</title>
    
    {{-- THE FIX: The opening style tag and full CSS was missing --}}
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
        .layout-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .details-col { 
            width: 75%; 
            vertical-align: top; 
        }
        .photo-col { 
            width: 25%; 
            vertical-align: top; 
            text-align: right; 
        }
        .photo-box { 
            border: 1px dashed #999; 
            width: 120px; 
            height: 140px; 
            display: inline-block; 
            text-align: center; 
            font-size: 11px; 
            color: #999; 
            padding-top: 50px;
            box-sizing: border-box;
        }
        p { margin: 5px 0; font-size: 13px; }
        .label { 
            font-weight: bold; 
            display: inline-block; 
            width: 200px; 
        }
        .signature-line { 
            border-top: 1px solid #000; 
            padding-top: 5px; 
            font-weight: bold; 
            font-size: 12px; 
        }

        /* Print Button Styles */
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

    <!-- Print Button Container (Hides when printing) -->
    <div class="no-print" style="text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <button onclick="window.print()" class="btn-print">🖨️ Print Loan Agreement</button>
    </div>

    @php
        // THE FIX: Force the system to prioritize the CURRENT logged-in business profile.
        // This stops the PDF from getting stuck on old data if the loan was migrated or created by a different admin account.
        $manager = Auth::user()->getCompany() ?? Auth::user()->loanManager ?? $loan->loanManager;
        
        $client = $loan->client;
        $currency = $manager->currency_symbol ?? 'UGX';
        $interest = $loan->interest_amount ?? ($loan->principal_amount * ($loan->interest_rate / 100));
        $totalDue = $loan->principal_amount + $interest + ($loan->processing_fee ?? 0);
        
        // Strict mapping: Ensure we don't accidentally pull blank database fields
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
    <p><span class="label">Total Amount to be Repaid:</span> <strong style="font-size: 14px;">{{ $currency }} {{ number_format($totalDue) }}</strong></p>
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
    <p style="font-size: 12px; text-align: justify; margin-bottom: 8px;"><strong>1. AGREEMENT:</strong> This Loan Agreement ("Agreement") is made on {{ \Carbon\Carbon::parse($loan->start_date)->format('F d, Y') }} by and between <strong>{{ $companyName }}</strong> ("Lender") and <strong>{{ $client->name }}</strong> ("Borrower").</p>
    <p style="font-size: 12px; text-align: justify; margin-bottom: 8px;"><strong>2. REPAYMENT:</strong> The Borrower agrees to repay the Total Repayable Amount of <strong>{{ $currency }} {{ number_format($totalDue) }}</strong> in installments as per the agreed-upon schedule.</p>
    <p style="font-size: 12px; text-align: justify; margin-bottom: 8px;"><strong>3. DEFAULT:</strong> Failure to make a payment for more than seven (7) days after the due date will be considered a default. In the event of default, the Lender has the right to demand immediate full payment of the outstanding balance and may seize any collateral listed.</p>
    <p style="font-size: 12px; text-align: justify; margin-bottom: 8px;"><strong>4. GOVERNING LAW:</strong> This Agreement shall be governed by and construed in accordance with the laws of Uganda. The borrower confirms the authenticity of the National ID and details provided herein.</p>

    <p style="font-size: 12px; margin-top: 40px; text-align: center;">By signing below, all parties agree to the terms and conditions of this loan agreement.</p>

    <table style="width: 100%; margin-top: 60px; text-align: center;">
        <tr>
            <td style="width: 33%; padding: 0 15px;">
                <div class="signature-line">Borrower's Signature</div>
                <div style="font-size: 11px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
            <td style="width: 33%; padding: 0 15px;">
                <div class="signature-line">Guarantor's Signature</div>
                <div style="font-size: 11px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
            <td style="width: 33%; padding: 0 15px;">
                <div class="signature-line">Lender: {{ $companyName }}</div>
                <div style="font-size: 11px; margin-top: 5px;">Authorized Signature & Date</div>
            </td>
        </tr>
    </table>
</body>
</html>