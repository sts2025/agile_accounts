<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $payment->reference_id ?? $payment->id }}</title>
    <style>
        /* THERMAL PRINTER STYLING */
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            background-color: #555;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }
        .receipt-wrapper {
            width: 80mm;
            max-width: 320px;
            background: #fff;
            padding: 15px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            height: fit-content;
        }
        
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .dashed-line {
            border-top: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
            display: block;
        }
        .double-line {
            border-top: 3px double #000;
            margin: 8px 0;
            width: 100%;
            display: block;
        }
        .quad-star {
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
        }

        /* Updated Header Styles for Logo */
        .header img.logo {
            max-width: 80px;
            max-height: 80px;
            display: block;
            margin: 0 auto 10px auto;
        }
        .header h2 { margin: 0; font-size: 16px; font-weight: 900; line-height: 1.2; }
        .header p { margin: 2px 0; font-size: 11px; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            width: 100%;
        }
        .label { font-size: 12px; }
        .value { font-size: 12px; font-weight: bold; text-align: right; }

        .sig-box { margin-top: 25px; }
        .sig-line {
            border-bottom: 1px dotted #000;
            height: 15px;
            width: 100%;
            margin-bottom: 5px;
        }

        .no-print {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 10px 0;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-family: sans-serif;
            font-size: 14px;
            font-weight: bold;
            box-sizing: border-box;
            cursor: pointer;
        }
        .btn-print { background: #000; color: #fff; border: 2px solid #000; }
        .btn-back { background: #fff; color: #333; border: 1px solid #ccc; }

        @media print {
            body { background: none; padding: 0; margin: 0; display: block; }
            .receipt-wrapper { box-shadow: none; width: 100%; max-width: 100%; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 0; size: auto; }
        }
    </style>
</head>
<body>

    @php
        $loan = $payment->loan;
        
        // FIX: Forcefully get the current logged-in manager if the loan relationship fails
        // This ensures the custom settings you just saved are loaded.
        $manager = $loan->loanManager ?? Auth::user()->loanManager; 
        
        $currency = $manager->currency_symbol ?? 'UGX';
        
        // Financials
        $calculatedInterest = optional($loan)->principal_amount * (optional($loan)->interest_rate / 100);
        $interestAmount = optional($loan)->interest_amount ?? $calculatedInterest ?? 0;
        
        $totalRepayable = optional($loan)->principal_amount + $interestAmount;
        $totalPaid = $payment->loan->payments->sum('amount_paid'); 
        $loan_balance = $totalRepayable - $totalPaid; 
        
        $principal_amount = number_format(optional($loan)->principal_amount ?? 0, 0, '.', '');
        
        $date_taken = 'N/A';
        $raw_date = optional($loan)->disbursement_date ?? optional($loan)->start_date;
        if ($raw_date) {
            try { $date_taken = date('d-m-Y', strtotime($raw_date)); } catch (\Exception $e) {}
        }
        
        // Client Contact
        $client = optional($loan)->client;
        $client_phone = optional($client)->phone_number ?? 'N/A';
        
        // --- COMPANY DETAILS ---
        // We prioritize the specific fields you added in Settings (company_name, etc.)
        // If those are empty, we fall back to the generic manager profile data.
        $companyName = $manager->company_name ?? optional($manager->user)->name ?? 'LOAN MANAGER';
        $companyPhone = $manager->company_phone ?? $manager->phone_number ?? 'N/A';
        $companyAddress = $manager->company_address ?? $manager->address ?? 'Main Branch';
        $companyEmail = $manager->company_email ?? null;
        $companyLogo = $manager->company_logo ?? null;
    @endphp

    <div class="receipt-wrapper">
        
        <!-- 1. COMPANY HEADER -->
        <div class="header text-center">
            {{-- Dynamic Logo --}}
            @if($companyLogo)
                <img src="{{ asset('storage/' . $companyLogo) }}" alt="Logo" class="logo">
            @endif

            {{-- Company Name --}}
            <h2 class="uppercase">{{ $companyName }}</h2>
            
            {{-- Address --}}
            <p>{{ $companyAddress }}</p>
            
            {{-- Contact Info --}}
            <p>Tel: {{ $companyPhone }}</p>
            @if($companyEmail)
                <p>Email: {{ $companyEmail }}</p>
            @endif
            
            <div class="double-line"></div>
            <p class="font-bold uppercase">*** PAYMENT RECEIPT ***</p>
            <div class="dashed-line"></div>
        </div>

        <!-- 2. LOAN DETAILS SECTION -->
        <div class="loan-details">
            <div class="info-row">
                <span class="label">Receipt No:</span>
                <span class="value">{{ $payment->receipt_number ?? $payment->reference_id ?? str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Loan Ref:</span>
                <span class="value">{{ optional($loan)->reference_id ?? '#'.$payment->loan_id }}</span>
            </div>

            <div class="info-row">
                <span class="label">Customer:</span>
                <span class="value uppercase">{{ optional($client)->name ?? 'N/A' }}</span>
            </div>
             <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value">{{ $client_phone }}</span>
            </div>
            
            <div class="dashed-line"></div>

            <!-- Financials -->
            <div class="info-row">
                <span class="label">Principal:</span>
                <span class="value">{{ $currency }} {{ number_format($principal_amount) }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Date Given:</span>
                <span class="value">{{ $date_taken }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Payment Date:</span>
                <span class="value">{{ $payment->payment_date ? date('d-m-Y', strtotime($payment->payment_date)) : date('d-m-Y') }}</span>
            </div>
        </div>

        <div class="quad-star">***** PAID *****</div>

        <!-- 3. PAYMENT FINANCIALS -->
        <div class="financials">
            <div class="info-row">
                <span class="label">Principal Paid:</span>
                <span class="value">{{ $currency }} {{ number_format($payment->principal_paid ?? 0, 0) }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Interest Paid:</span>
                <span class="value">{{ $currency }} {{ number_format($payment->interest_paid ?? 0, 0) }}</span>
            </div>

            <div class="dashed-line"></div>

            <div class="info-row" style="font-size: 16px; margin: 10px 0;">
                <span class="label font-bold">TOTAL PAID:</span>
                <span class="value font-bold">{{ $currency }} {{ number_format($payment->amount_paid ?? 0, 0) }}</span>
            </div>
            
            <div class="dashed-line"></div>
            
            <div class="info-row">
                <span class="label">New Balance:</span>
                <span class="value">{{ $currency }} {{ number_format(max(0, $loan_balance), 0) }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Method:</span>
                <span class="value">{{ ucfirst($payment->payment_method ?? 'Cash') }}</span>
            </div>
        </div>

        <div class="double-line"></div>

        <!-- 4. SIGNATURES -->
        <div class="sig-box">
            <div class="label">Cashier: <span class="font-bold">{{ strtoupper(auth()->user()->name ?? 'SYSTEM') }}</span></div>
            <div class="label" style="margin-top: 15px;">Signature:</div>
            <div class="sig-line"></div>
        </div>

        <div class="text-center" style="margin-top: 20px; font-size: 10px;">
            Thank you for doing business with us!
        </div>

        <div class="no-print">
            <button onclick="window.print()" class="btn btn-print">Print Receipt</button>
            <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-back">Back to Loan</a>
        </div>

    </div>

</body>
</html>