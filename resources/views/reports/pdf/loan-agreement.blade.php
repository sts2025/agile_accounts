<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - {{ $loan->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; line-height: 1.5; color: #333; }
        .header { text-align: center; }
        .header h1, .header h2 { margin: 0; padding: 0; }
        h1 { font-size: 22px; }
        h2 { font-size: 18px; font-weight: normal; }
        h3 { font-size: 14px; border-bottom: 1px solid #000; padding-bottom: 5px; margin-top: 20px; margin-bottom: 10px;}
        p { margin: 4px 0; }
        .section { margin-top: 15px; page-break-inside: avoid; }
        .signature-section { margin-top: 50px; page-break-inside: avoid; }
        .signature-block { display: inline-block; width: 30%; margin: 0 1.5%; text-align: center; }
        .signature-line { border-bottom: 1px solid #333; height: 40px; margin-top: 40px; }
        .signature-title { font-size: 10px; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .photo-box {
            width: 100px;
            height: 120px;
            border: 1px dashed #999;
            text-align: center;
            color: #999;
            float: right;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 10px;
        }
        .details-container { width: 75%; float: left; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOAN AGREEMENT</h1>
        <h2>Agile Accounts</h2>
    </div>

    <div class="section clearfix">
        <h3>BORROWER DETAILS</h3>
        <div class="photo-box"><span>Affix Borrower<br>Passport Photo</span></div>
        <div class="details-container">
            <p><strong>Name:</strong> {{ $loan->client->name }}</p>
            <p><strong>Phone:</strong> {{ $loan->client->phone_number }}</p>
            <p><strong>Address:</strong> {{ $loan->client->address }}</p>
            <p><strong>Business/Occupation:</strong> {{ $loan->client->business_occupation ?? 'N/A' }}</p>
        </div>
    </div>
    
    <div class="section">
        <h3>LOAN DETAILS</h3>
        @php
            $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
            $totalRepayable = $loan->principal_amount + $totalInterest;
        @endphp
        <p><strong>Loan Amount (Principal):</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
        <p><strong>Processing Fee (One-time):</strong> UGX {{ number_format($loan->processing_fee, 0) }}</p>
        <p><strong>Interest Amount:</strong> UGX {{ number_format($totalInterest, 0) }} ({{$loan->interest_rate}}% Flat Rate)</p>
        <p><strong>Total Amount to be Repaid:</strong> <strong>UGX {{ number_format($totalRepayable, 0) }}</strong></p>
        <p><strong>Term:</strong> {{ $loan->term }} {{ $loan->repayment_frequency }} payments</p>
        <p><strong>Disbursement Date:</strong> {{ \Carbon\Carbon::parse($loan->start_date)->format('F d, Y') }}</p>
    </div>
    
    @if($loan->guarantors->isNotEmpty())
    <div class="section">
        <h3>GUARANTOR DETAILS</h3>
        @foreach($loan->guarantors as $guarantor)
            <div class="clearfix" style="margin-bottom: 15px; padding-top: 10px;">
                <div class="photo-box"><span>Affix Guarantor<br>Passport Photo</span></div>
                <div class="details-container">
                    <p><strong>Name:</strong> {{ $guarantor->first_name }} {{ $guarantor->last_name }}</p>
                    <p><strong>Phone:</strong> {{ $guarantor->phone_number }}</p>
                    <p><strong>Address:</strong> {{ $guarantor->address }}</p>
                    <p><strong>Occupation:</strong> {{ $guarantor->occupation ?? 'N/A' }}</p>
                    <p><strong>Relationship:</strong> {{ $guarantor->relationship_to_borrower }}</p>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- This is the restored Collateral Details section --}}
    @if($loan->collaterals->isNotEmpty())
    <div class="section">
        <h3>COLLATERAL DETAILS</h3>
         @foreach($loan->collaterals as $collateral)
            <div style="margin-bottom: 10px; border-left: 2px solid #ccc; padding-left: 10px;">
                <p><strong>Type:</strong> {{ $collateral->collateral_type }}</p>
                <p><strong>Description:</strong> {{ $collateral->description }}</p>
                <p><strong>Valuation:</strong> UGX {{ number_format($collateral->valuation_amount, 0) }}</p>
            </div>
        @endforeach
    </div>
    @endif
    
    {{-- This is the restored Signature section --}}
    <div class="signature-section">
        <p>By signing below, all parties agree to the terms and conditions of this loan agreement.</p>
        
        <div class="signature-block">
            <div class="signature-line"></div>
            <p class="signature-title">Borrower's Signature ({{ $loan->client->name }})</p>
        </div>
        
        {{-- This now correctly creates a signature line for each guarantor --}}
        @foreach($loan->guarantors as $guarantor)
        <div class="signature-block">
            <div class="signature-line"></div>
            <p class="signature-title">Guarantor's Signature ({{ $guarantor->first_name }} {{ $guarantor->last_name }})</p>
        </div>
        @endforeach
        
        <div class="signature-block">
            <div class="signature-line"></div>
            <p class="signature-title">Approved By (Loan Officer)</p>
        </div>
    </div>
</body>
</html>