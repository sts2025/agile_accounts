<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - <?php echo e($loan->client->name); ?></title>
    <style>
        /* DomPDF compatible CSS */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.4;
            margin: 0;
            padding: 10px 20px;
        }
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }
        .company-details {
            font-size: 11px;
            color: #444;
        }
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 25px;
        }
        .section-header {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 3px;
        }
        
        /* Layout Tables for Photo Boxes */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        .layout-table td {
            vertical-align: top;
        }
        .details-col {
            width: 75%;
        }
        .photo-col {
            width: 25%;
            text-align: right;
        }
        
        .photo-box {
            border: 1px dashed #666;
            width: 110px;
            height: 130px;
            display: inline-block;
            text-align: center;
            color: #666;
            font-size: 9px;
            padding-top: 50px;
            box-sizing: border-box;
        }
        
        p { margin: 0 0 8px 0; }
        .label { font-weight: bold; }
        
        .terms-list {
            margin-top: 10px;
            padding-left: 0;
            list-style-type: none;
        }
        .terms-list li {
            margin-bottom: 12px;
            text-align: justify;
            font-size: 11px;
        }
        
        .signatures {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 15px;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <?php
        $manager = $loan->loanManager;
        $client = $loan->client;
        $currency = $manager->currency_symbol ?? 'UGX';
        
        $interest = $loan->interest_amount ?? ($loan->principal_amount * ($loan->interest_rate / 100));
        $totalDue = $loan->principal_amount + $interest + ($loan->processing_fee ?? 0);
    ?>

    <div class="header-section">
        <h1 class="company-name"><?php echo e($manager->company_name ?? 'STREAMLINE TECH SOLUTION'); ?></h1>
        <div class="company-details">
            <?php echo e($manager->address ?? 'Company Address'); ?> | Phone: <?php echo e($manager->phone_number ?? 'N/A'); ?> | Email: <?php echo e(Auth::user()->email); ?>

        </div>
    </div>

    <div class="document-title">
        LOAN AGREEMENT
    </div>

    <!-- BORROWER DETAILS -->
    <div class="section-header">BORROWER DETAILS</div>
    <table class="layout-table">
        <tr>
            <td class="details-col">
                <p><span class="label">Name:</span> <?php echo e($client->name); ?></p>
                <p><span class="label">Phone:</span> <?php echo e($client->phone_number); ?></p>
                <p><span class="label">National ID (NIN):</span> <?php echo e($client->national_id ?? '_________________'); ?></p>
                <p><span class="label">Date of Birth:</span> <?php echo e($client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('d M, Y') : '_________________'); ?></p>
                <p><span class="label">Address:</span> <?php echo e($client->address); ?></p>
                <p><span class="label">Business/Occupation:</span> <?php echo e($client->business_occupation ?? 'N/A'); ?></p>
            </td>
            <td class="photo-col">
                <div class="photo-box">
                    Affix Borrower<br>Passport Photo
                </div>
            </td>
        </tr>
    </table>

    <!-- LOAN DETAILS -->
    <div class="section-header">LOAN DETAILS</div>
    <p><span class="label">Loan Amount (Principal):</span> <?php echo e($currency); ?> <?php echo e(number_format($loan->principal_amount)); ?></p>
    <p><span class="label">Processing Fee (One-time):</span> <?php echo e($currency); ?> <?php echo e(number_format($loan->processing_fee ?? 0)); ?></p>
    <p><span class="label">Interest Amount:</span> <?php echo e($currency); ?> <?php echo e(number_format($interest)); ?> (<?php echo e($loan->interest_rate); ?>% Flat Rate)</p>
    <p><span class="label">Total Amount to be Repaid:</span> <?php echo e($currency); ?> <?php echo e(number_format($totalDue)); ?></p>
    <p><span class="label">Term:</span> <?php echo e($loan->duration ?? '____'); ?> <?php echo e($loan->duration_period ?? 'Months'); ?></p>
    <p><span class="label">Disbursement Date:</span> <?php echo e(\Carbon\Carbon::parse($loan->start_date)->format('F d, Y')); ?></p>

    <!-- GUARANTOR DETAILS -->
    <div class="section-header">GUARANTOR DETAILS</div>
    <table class="layout-table">
        <tr>
            <td class="details-col">
                <?php if($loan->guarantors && $loan->guarantors->count() > 0): ?>
                    <?php $g = $loan->guarantors->first(); ?>
                    <p><span class="label">Name:</span> <?php echo e($g->first_name); ?> <?php echo e($g->last_name); ?></p>
                    <p><span class="label">Phone:</span> <?php echo e($g->phone_number); ?></p>
                    <p><span class="label">Address:</span> <?php echo e($g->address); ?></p>
                    <p><span class="label">Occupation:</span> <?php echo e($g->occupation ?? 'N/A'); ?></p>
                    <p><span class="label">Relationship:</span> <?php echo e($g->relationship_to_borrower ?? 'N/A'); ?></p>
                <?php elseif($loan->guarantor_name): ?>
                    <p><span class="label">Name:</span> <?php echo e($loan->guarantor_name); ?></p>
                    <p><span class="label">Phone:</span> <?php echo e($loan->guarantor_phone); ?></p>
                    <p><span class="label">Address:</span> <?php echo e($loan->guarantor_address ?? 'N/A'); ?></p>
                    <p><span class="label">Relationship:</span> <?php echo e($loan->guarantor_relationship ?? 'N/A'); ?></p>
                <?php else: ?>
                    <p><em>No guarantor details recorded for this loan.</em></p>
                <?php endif; ?>
            </td>
            <td class="photo-col">
                <div class="photo-box">
                    Affix Guarantor<br>Passport Photo
                </div>
            </td>
        </tr>
    </table>

    <!-- COLLATERAL DETAILS -->
    <div class="section-header">COLLATERAL DETAILS</div>
    <?php if($loan->collaterals && $loan->collaterals->count() > 0): ?>
        <?php $__currentLoopData = $loan->collaterals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <p><span class="label">Type:</span> <?php echo e($c->collateral_type ?? 'N/A'); ?></p>
            <p><span class="label">Description:</span> <?php echo e($c->name); ?> - <?php echo e($c->description); ?></p>
            <p><span class="label">Valuation:</span> <?php echo e($currency); ?> <?php echo e(number_format($c->valuation_amount ?? $c->value ?? 0)); ?></p>
            <?php if(!$loop->last): ?><hr style="border:0; border-top: 1px dashed #ccc; margin: 10px 0;"><?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php elseif($loan->collateral_name): ?>
        <p><span class="label">Type / Name:</span> <?php echo e($loan->collateral_name); ?></p>
        <p><span class="label">Description:</span> <?php echo e($loan->collateral_description ?? 'N/A'); ?></p>
        <p><span class="label">Valuation:</span> <?php echo e($currency); ?> <?php echo e(number_format((float)$loan->collateral_value)); ?></p>
    <?php else: ?>
        <p><em>No collateral details recorded for this loan.</em></p>
    <?php endif; ?>

    <!-- TERMS AND CONDITIONS -->
    <div class="section-header">TERMS AND CONDITIONS</div>
    <ul class="terms-list">
        <li><strong>1. AGREEMENT:</strong> This Loan Agreement ("Agreement") is made on <?php echo e(\Carbon\Carbon::parse($loan->start_date)->format('F d, Y')); ?> by and between <?php echo e($manager->company_name ?? 'the Lender'); ?> ("Lender") and <strong><?php echo e($client->name); ?></strong> ("Borrower").</li>
        <li><strong>2. REPAYMENT:</strong> The Borrower agrees to repay the Total Repayable Amount of <strong><?php echo e($currency); ?> <?php echo e(number_format($totalDue)); ?></strong> in installments as per the agreed-upon schedule.</li>
        <li><strong>3. DEFAULT:</strong> Failure to make a payment for more than seven (7) days after the due date will be considered a default. In the event of default, the Lender has the right to demand immediate full payment of the outstanding balance and may seize any collateral listed.</li>
        <li><strong>4. GOVERNING LAW:</strong> This Agreement shall be governed by and construed in accordance with the laws of Uganda. The borrower confirms the authenticity of the National ID and details provided herein.</li>
    </ul>

    <p style="font-size: 10px; margin-top: 30px; text-align: center;">By signing below, all parties agree to the terms and conditions of this loan agreement.</p>

    <!-- SIGNATURES -->
    <table class="signatures">
        <tr>
            <td>
                <br><br><br>
                <div class="signature-line">Borrower's Signature</div>
                <div style="font-size: 10px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
            <td>
                <br><br><br>
                <div class="signature-line">Guarantor's Signature</div>
                <div style="font-size: 10px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
            <td>
                <br><br><br>
                <div class="signature-line">Lender / Manager</div>
                <div style="font-size: 10px; margin-top: 5px;">Date: ____/____/20___</div>
            </td>
        </tr>
    </table>

</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/loans/agreement-pdf.blade.php ENDPATH**/ ?>