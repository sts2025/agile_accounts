<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>

<?php $__env->startSection('title', 'Trial Balance'); ?>
<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h1>Trial Balance</h1>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button> 
    </div>
    <div class="card-body">
        <p class="text-muted">As of: <?php echo e(\Carbon\Carbon::now()->format('F d, Y H:i:s')); ?></p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60%;">Account Name</th>
                        <th class="text-end" style="width: 20%;">Debit (<?php echo e($currency); ?>)</th> 
                        <th class="text-end" style="width: 20%;">Credit (<?php echo e($currency); ?>)</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    ?>

                    <?php $__empty_1 = true; $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $debit = $account->debit_balance ?? 0;
                        $credit = $account->credit_balance ?? 0;
                        
                        $totalDebit += $debit;
                        $totalCredit += $credit;
                    ?>
                    <tr>
                        <td><?php echo e($account->name); ?></td>
                        <td class="text-end"><?php echo e(number_format($debit, 2)); ?></td>
                        <td class="text-end"><?php echo e(number_format($credit, 2)); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="3" class="text-center">No accounts found to generate Trial Balance.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-dark fw-bold">
                    <tr>
                        <td>TOTALS</td>
                        <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalDebit, 2)); ?></td> 
                        <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalCredit, 2)); ?></td> 
                    </tr>
                    <?php if(abs($totalDebit - $totalCredit) > 0.01): ?> 
                    <tr class="table-danger">
                        <td colspan="3" class="text-center">
                            WARNING: Debits and Credits do not balance! Difference: <?php echo e($currency); ?> <?php echo e(number_format(abs($totalDebit - $totalCredit), 2)); ?>

                        </td> 
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/trial-balance.blade.php ENDPATH**/ ?>