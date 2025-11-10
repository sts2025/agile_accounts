<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
    <style>
        /* Basic thermal receipt styling */
        body { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4; color: #000; background-color: #fff; width: 280px; margin: 0 auto; padding: 10px; }
        .header, .footer { text-align: center; }
        .header h3, .header p { margin: 0; }
        .details { margin-top: 15px; }
        .details p { margin: 5px 0; display: flex; justify-content: space-between; }
        .separator { border-top: 1px dashed #000; margin: 10px 0; }
        .total { font-weight: bold; font-size: 14px; }
        .no-print { margin-top: 20px; text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <?php if(Auth::user()->loanManager->company_logo_path): ?>
                <img src="<?php echo e(storage_path('app/public/' . Auth::user()->loanManager->company_logo_path)); ?>" alt="Logo" style="max-width: 150px; margin-bottom: 10px;">
            <?php endif; ?>
            <h3><?php echo e(Auth::user()->loanManager->company_name ?? 'Agile Accounts'); ?></h3>
            <p><?php echo e(Auth::user()->loanManager->company_phone); ?></p>
            <p><strong>Payment Receipt</strong></p>
        </div>

        <div class="separator"></div>

        <div class="details">
            <p><span>Receipt No:</span> <span><?php echo e($payment->id); ?></span></p>
            <p><span>Date:</span> <span><?php echo e(\Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y H:i')); ?></span></p>
            <p><span>Client:</span> <span><?php echo e($payment->loan->client->name); ?></span></p>
        </div>

        <div class="separator"></div>

        <div class="details">
            <p><span>Amount Paid:</span> <span>UGX <?php echo e(number_format($payment->amount_paid)); ?></span></p>
            <p><span>Paid By:</span> <span><?php echo e($payment->payment_method); ?></span></p>
            <p class="total"><span>New Balance:</span> <span>UGX <?php echo e(number_format($loanBalance)); ?></span></p>
        </div>
        
        <div class="separator"></div>

        <div class="footer">
            <p>Thank you for your business!</p>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Print</button>
        <a href="<?php echo e(route('dashboard')); ?>" style="padding: 10px 20px; font-size: 16px;">Back to Dashboard</a>
    </div>

</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/payments/receipt-thermal.blade.php ENDPATH**/ ?>