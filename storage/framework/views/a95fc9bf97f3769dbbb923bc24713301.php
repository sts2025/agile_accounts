<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>

<?php $__env->startSection('title', 'Profit & Loss Report'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mt-4">
    <h1 class="mb-4">Profit & Loss Report</h1>
    <h5>Period: <?php echo e(\Carbon\Carbon::parse($startDate)->format('d M, Y')); ?> - <?php echo e(\Carbon\Carbon::parse($endDate)->format('d M, Y')); ?></h5>

    <div class="row mt-4">
        
        <div class="col-md-6 mb-4">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="mb-0 text-primary">Income</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $incomeAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php if($account->period_total != 0): ?>
                                    <tr>
                                        <td class="ps-3"><?php echo e($account->name); ?></td>
                                        <td class="text-end pe-3"><?php echo e(number_format($account->period_total, 0)); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="2" class="text-center text-muted p-3">No income recorded for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Total Income</td>
                                <td class="text-end pe-3"><?php echo e($currency); ?> <?php echo e(number_format($totalIncome, 0)); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="col-md-6 mb-4">
            <div class="card border-start border-danger border-4 h-100">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="mb-0 text-danger">Expenses</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $expenseAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php if($account->period_total != 0): ?>
                                    <tr>
                                        <td class="ps-3"><?php echo e($account->name); ?></td>
                                        <td class="text-end pe-3"><?php echo e(number_format($account->period_total, 0)); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="2" class="text-center text-muted p-3">No expenses recorded for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Total Expenses</td>
                                <td class="text-end pe-3"><?php echo e($currency); ?> <?php echo e(number_format($totalExpenses, 0)); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="card <?php if($netProfit >= 0): ?> bg-success <?php else: ?> bg-danger <?php endif; ?> text-white shadow">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0">Net Profit</h4>
                    <h4 class="mb-0"><?php echo e($currency); ?> <?php echo e(number_format($netProfit, 0)); ?></h4>
                </div>
            </div>
            <a href="https://wa.me/?text=<?php echo e($whatsappMessage); ?>" target="_blank" class="btn btn-success mt-3 no-print">
                <i class="bi bi-whatsapp me-2"></i>Share via WhatsApp
            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/profit-and-loss.blade.php ENDPATH**/ ?>