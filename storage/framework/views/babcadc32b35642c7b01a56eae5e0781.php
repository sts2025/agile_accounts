<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - <?php echo e($loan->id); ?></title>
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
        .terms { font-size: 11px; text-align: justify; }
        .terms p { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOAN AGREEMENT</h1>
        
        <h2><?php echo e($loanManager->business_name); ?></h2>
    </div>

    <div class="section clearfix">
        <h3>BORROWER DETAILS</h3>
        <div class="photo-box"><span>Affix Borrower<br>Passport Photo</span></div>
        <div class="details-container">
            <p><strong>Name:</strong> <?php echo e($loan->client->name); ?></p>
            <p><strong>Phone:</strong> <?php echo e($loan->client->phone_number); ?></p>
            <p><strong>Address:</strong> <?php echo e($loan->client->address); ?></p>
            <p><strong>Business/Occupation:</strong> <?php echo e($loan->client->business_occupation ?? 'N/A'); ?></p>
        </div>
    </div>
    
    <div class="section">
        <h3>LOAN DETAILS</h3>
        <?php
            $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
            $totalRepayable = $loan->principal_amount + $totalInterest;
        ?>
        <p><strong>Loan Amount (Principal):</strong> UGX <?php echo e(number_format($loan->principal_amount, 0)); ?></p>
        <p><strong>Processing Fee (One-time):</strong> UGX <?php echo e(number_format($loan->processing_fee, 0)); ?></p>
        <p><strong>Interest Amount:</strong> UGX <?php echo e(number_format($totalInterest, 0)); ?> (<?php echo e($loan->interest_rate); ?>% Flat Rate)</p>
        <p><strong>Total Amount to be Repaid:</strong> <strong>UGX <?php echo e(number_format($totalRepayable, 0)); ?></strong></p>
        <p><strong>Term:</strong> <?php echo e($loan->term); ?> <?php echo e($loan->repayment_frequency); ?> payments</p>
        <p><strong>Disbursement Date:</strong> <?php echo e(\Carbon\Carbon::parse($loan->start_date)->format('F d, Y')); ?></p>
    </div>
    
    <?php if($loan->guarantors->isNotEmpty()): ?>
    <div class="section">
        <h3>GUARANTOR DETAILS</h3>
        <?php $__currentLoopData = $loan->guarantors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guarantor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="clearfix" style="margin-bottom: 15px; padding-top: 10px;">
                <div class="photo-box"><span>Affix Guarantor<br>Passport Photo</span></div>
                <div class="details-container">
                    <p><strong>Name:</strong> <?php echo e($guarantor->first_name); ?> <?php echo e($guarantor->last_name); ?></p>
                    <p><strong>Phone:</strong> <?php echo e($guarantor->phone_number); ?></p>
                    <p><strong>Address:</strong> <?php echo e($guarantor->address); ?></p>
                    <p><strong>Occupation:</strong> <?php echo e($guarantor->occupation ?? 'N/A'); ?></p>
                    <p><strong>Relationship:</strong> <?php echo e($guarantor->relationship_to_borrower); ?></p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    <?php if($loan->collaterals->isNotEmpty()): ?>
    <div class="section">
        <h3>COLLATERAL DETAILS</h3>
         <?php $__currentLoopData = $loan->collaterals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collateral): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="margin-bottom: 10px; border-left: 2px solid #ccc; padding-left: 10px;">
                <p><strong>Type:</strong> <?php echo e($collateral->collateral_type); ?></p>
                <p><strong>Description:</strong> <?php echo e($collateral->description); ?></p>
                <p><strong>Valuation:</strong> UGX <?php echo e(number_format($collateral->valuation_amount, 0)); ?></p>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    
    <div class="section terms">
        <h3>TERMS AND CONDITIONS</h3>
        <p>
            <strong>1. AGREEMENT:</strong> This Loan Agreement ("Agreement") is made on <?php echo e(\Carbon\Carbon::parse($loan->start_date)->format('F d, Y')); ?> by and between
            <strong><?php echo e($loanManager->business_name); ?></strong> ("Lender") and <strong><?php echo e($loan->client->name); ?></strong> ("Borrower").
        </p>
        <p>
            <strong>2. REPAYMENT:</strong> The Borrower agrees to repay the Total Repayable Amount of <strong>UGX <?php echo e(number_format($totalRepayable, 0)); ?></strong>
            in <?php echo e($loan->term); ?> <?php echo e($loan->repayment_frequency); ?> installments as per the agreed-upon schedule.
        </p>
        <p>
            <strong>3. DEFAULT:</strong> Failure to make a payment for more than seven (7) days after the due date will be considered a default. In the event of default, the Lender has the right to demand immediate full payment of the outstanding balance and may seize any collateral listed.
        </p>
        <p>
            <strong>4. GOVERNING LAW:</strong> This Agreement shall be governed by and construed in accordance with the laws of Uganda.
        </p>
    </div>
    
    
    <div class="signature-section">
        <p>By signing below, all parties agree to the terms and conditions of this loan agreement.</p>
        
        <div class="signature-block">
            <div class="signature-line"></div>
            <p class="signature-title">Borrower's Signature (<?php echo e($loan->client->name); ?>)</p>
        </div>
        
        <?php $__currentLoopData = $loan->guarantors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guarantor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="signature-block">
            <div class="signature-line"></div>
            <p class="signature-title">Guarantor's Signature (<?php echo e($guarantor->first_name); ?> <?php echo e($guarantor->last_name); ?>)</p>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
        
        <div class="signature-block">
            <div class="signature-line"></div>
            
            <p class="signature-title">Approved By: <?php echo e($loanManager->user->name ?? 'Loan Officer'); ?></p>
            <p class="signature-title">(For <?php echo e($loanManager->business_name); ?>)</p>
        </div>
    </div>
</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/reports/pdf/loan-agreement.blade.php ENDPATH**/ ?>