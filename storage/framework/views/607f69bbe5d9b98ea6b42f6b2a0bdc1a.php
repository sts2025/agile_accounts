<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo e($payment->id ?? 'N/A'); ?></title>
    <style>
        /* General styles, mostly unchanged from previous versions, ensuring thermal look */
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
        
        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Dividers */
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

        /* Elements */
        .header h2 { margin: 0; font-size: 16px; font-weight: 900; }
        .header p { margin: 2px 0; font-size: 11px; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            width: 100%;
        }
        .label { font-size: 12px; }
        .value { font-size: 12px; font-weight: bold; text-align: right; }

        /* Signatures */
        .sig-box { margin-top: 25px; }
        .sig-line {
            border-bottom: 1px dotted #000;
            height: 15px;
            width: 100%;
            margin-bottom: 5px;
        }

        /* Buttons */
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

    <?php
        // --- 1. VARIABLE SETUP & CURRENCY FIX ---
        $loan = $payment->loan;
        $manager = optional($loan)->manager;
        $currency = optional($manager)->currency_symbol ?? 'UGX';
        
        // --- FINANCIAL CALCULATION FIXES ---
        $calculatedInterest = optional($loan)->principal_amount * (optional($loan)->interest_rate / 100);
        $interestAmount = optional($loan)->interest_amount ?? $calculatedInterest ?? 0;
        
        // Total Repayable is Principal + Interest (Processing Fee excluded from balance calculation)
        $totalRepayable = optional($loan)->principal_amount + $interestAmount;
        $totalPaid = $payment->loan->payments->sum('amount_paid'); 
        $loan_balance = $totalRepayable - $totalPaid; 
        
        $loan_arrears = optional($loan)->arrears ?? 0;
        $principal_amount = number_format(optional($loan)->principal_amount ?? optional($loan)->amount_given ?? 0, 0, '.', '');
        
        // --- DATE TAKEN FIX (Robust parsing to ensure a date is displayed) ---
        $date_taken = 'N/A';
        $raw_date = optional($loan)->disbursement_date;
        if ($raw_date) {
            try {
                // Try parsing the date, first as a standard timestamp/string, then fall back if needed
                $date_taken = date('d-m-Y', strtotime($raw_date));
            } catch (\Exception $e) {
                // If parsing fails, stick to N/A
            }
        }
        
        $status = optional($loan)->status ?? 'N/A';
        
        // Client and Manager details
        $client = optional($loan)->client;
        $client_phone = optional($client)->phone ?? 'N/A';
        $manager_phone = optional($manager)->support_phone ?? 'N/A';
    ?>

    <div class="receipt-wrapper">
        
        <!-- 1. COMPANY HEADER -->
        <div class="header text-center">
            <h2 class="uppercase"><?php echo e(optional($manager)->company_name ?? 'AGILE ACCOUNTS'); ?></h2>
            <p><?php echo e(optional($manager)->address ?? 'Main Branch'); ?></p>
            <p><?php echo e($manager_phone); ?></p>
            
            <div class="double-line"></div>
            <p class="font-bold uppercase">*** LOAN PAYMENT RECEIPT <?php echo e($payment->id ?? ''); ?> ***</p>
            <div class="dashed-line"></div>
        </div>

        <!-- 2. LOAN DETAILS SECTION -->
        <div class="loan-details">
            <div class="info-row">
                <span class="label">Receipt No:</span>
                <span class="value"><?php echo e($payment->receipt_number ?? 'N/A'); ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">Loan ID:</span>
                <span class="value"><?php echo e(optional($loan)->reference_id ?? 'LN-'.$payment->loan_id); ?></span>
            </div>

            <div class="info-row">
                <span class="label">Customer:</span>
                <span class="value uppercase"><?php echo e(optional($client)->name ?? 'N/A'); ?></span>
            </div>
             <div class="info-row">
                <span class="label">Customer Phone:</span>
                <span class="value"><?php echo e($client_phone); ?></span>
            </div>
            
            <!-- Principal Amount -->
            <div class="info-row">
                <span class="label">Principal Amount:</span>
                <span class="value"><?php echo e($currency); ?> <?php echo e($principal_amount); ?>/=</span>
            </div>
            
            <!-- Interest Amount (Explicit and using fallback calc) -->
            <div class="info-row">
                <span class="label">Interest Amount:</span>
                <span class="value"><?php echo e($currency); ?> <?php echo e(number_format($interestAmount, 0)); ?>/=</span>
            </div>

            <!-- Date Taken (Disbursement Date) -->
            <div class="info-row">
                <span class="label">Date Taken (Given):</span>
                <span class="value"><?php echo e($date_taken); ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value"><?php echo e($status); ?></span>
            </div>
             <div class="info-row">
                <span class="label">Payment Date:</span>
                <span class="value"><?php echo e($payment->payment_date ? date('d-m-Y', strtotime($payment->payment_date)) : date('d-m-Y')); ?></span>
            </div>
        </div>

        <div class="quad-star">*****Payment Details*****</div>

        <!-- 3. PAYMENT FINANCIALS SECTION -->
        <div class="financials">
            <div class="info-row" style="font-size: 14px;">
                <span class="label font-bold">Amount Paid:</span>
                <span class="value font-bold"><?php echo e($currency); ?> <?php echo e(number_format($payment->amount_paid ?? 0, 0)); ?>/=</span>
            </div>
            
            <!-- Balance calculation uses P+I only -->
            <div class="info-row">
                <span class="label">New Loan Balance:</span>
                <span class="value"><?php echo e($currency); ?> <?php echo e(number_format(max(0, $loan_balance), 0)); ?>/=</span>
            </div>
            
            <div class="info-row">
                <span class="label">Arrears:</span>
                <span class="value"><?php echo e($currency); ?> <?php echo e(number_format($loan_arrears, 0)); ?>/=</span>
            </div>

            <div class="dashed-line"></div>
             <div class="info-row">
                <span class="label">Paid Method:</span>
                <span class="value"><?php echo e(ucfirst($payment->payment_method ?? 'Cash')); ?></span>
            </div>
        </div>

        <div class="double-line"></div>

        <!-- 4. SIGNATURES -->
        <div class="sig-box">
            <div class="label">Cashier: <span class="font-bold"><?php echo e(strtoupper(auth()->user()->name ?? 'SYSTEM')); ?></span></div>
            <div class="label">Cashier Contact: <span class="font-bold"><?php echo e($manager_phone); ?></span></div>
            <div class="label" style="margin-top: 10px;">Cashier Signature:</div>
            <div class="sig-line"></div>
        </div>

        <div class="sig-box">
            <div class="label">Customer Signature:</div>
            <div class="sig-line"></div>
        </div>

        <div class="text-center" style="margin-top: 20px; font-size: 10px;">
            Thank you for choosing us!
        </div>

        <!-- 5. ACTIONS (Non-Printable) -->
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-print">Print Receipt</button>
            <a href="<?php echo e(route('loans.show', $loan->id)); ?>" class="btn btn-back">View Loan Details</a>
        </div>

    </div>

</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/payments/receipt-thermal.blade.php ENDPATH**/ ?>