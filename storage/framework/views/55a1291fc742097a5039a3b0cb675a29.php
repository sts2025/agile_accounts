<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>


<?php $__env->startSection('title', 'Daily Transaction Report'); ?>
<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Daily Transaction Report</h1>
            <p class="text-muted">For date: <?php echo e(\Carbon\Carbon::parse($reportDate)->format('F d, Y')); ?></p>
        </div>
        <div>
            
            <a href="<?php echo e(route('reports.daily.pdf', ['date' => $reportDate])); ?>" class="btn btn-primary" target="_blank">Print Report</a> 
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('reports.daily')); ?>" class="row g-3 align-items-center">
                <div class="col-auto"><label class="form-label">Select Date</label><input type="date" class="form-control" name="date" value="<?php echo e($reportDate); ?>"></div>
                <div class="col-auto mt-4"><button type="submit" class="btn btn-primary">View Report</button></div>
            </form>
        </div>
    </div>
    <div class="row mb-4">
        
        <div class="col-md-3"><div class="card text-white bg-danger h-100"><div class="card-body"><h5 class="card-title">Total Given Out</h5><p class="card-text fs-4"><?php echo e($currency); ?> <?php echo e(number_format($summary['total_loaned_principal'], 0)); ?></p><small><?php echo e($summary['count_loans_given']); ?> Loans</small></div></div></div>
        
        <div class="col-md-3"><div class="card text-white bg-success h-100"><div class="card-body"><h5 class="card-title">Total Received</h5><p class="card-text fs-4"><?php echo e($currency); ?> <?php echo e(number_format($summary['total_payments_received'], 0)); ?></p><small><?php echo e($summary['count_payments_received']); ?> Payments</small></div></div></div>
        
        <div class="col-md-3"><div class="card text-white bg-info h-100"><div class="card-body"><h5 class="card-title">Processing Fees</h5><p class="card-text fs-4"><?php echo e($currency); ?> <?php echo e(number_format($summary['total_processing_fees'], 0)); ?></p><small>from <?php echo e($summary['count_loans_given']); ?> Loans</small></div></div></div>
        
        <div class="col-md-3"><div class="card text-white bg-dark h-100"><div class="card-body"><h5 class="card-title">Net Cash Flow</h5><?php $netCashFlow = $summary['total_payments_received'] - $summary['total_loaned_principal']; ?><p class="card-text fs-4 <?php if($netCashFlow < 0): ?> text-warning <?php endif; ?>"><?php echo e($currency); ?> <?php echo e(number_format($netCashFlow, 0)); ?></p><small>Received minus Given</small></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h4>Loans Given Out Details</h4></div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Client Name</th><th class="text-end">Principal Amount (<?php echo e($currency); ?>)</th><th class="text-end">Processing Fee (<?php echo e($currency); ?>)</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $loansGiven; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr><td><?php echo e($loan->client->name); ?></td><td class="text-end"><?php echo e(number_format($loan->principal_amount, 0)); ?></td><td class="text-end"><?php echo e(number_format($loan->processing_fee, 0)); ?></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="text-center">No loans were given out on this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h4>Payments Received Details</h4></div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Client Name</th><th>Receipt #</th><th class="text-end">Amount Paid (<?php echo e($currency); ?>)</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $paymentsReceived; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr><td><?php echo e($payment->loan->client->name); ?></td><td><?php echo e($payment->receipt_number); ?></td><td class="text-end"><?php echo e(number_format($payment->amount_paid, 0)); ?></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="text-center">No payments were received on this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/daily-report.blade.php ENDPATH**/ ?>