<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency() ?? 'UGX';
// FIX: Safely define $endDate if the controller didn't pass it
$endDate = $endDate ?? request('end_date', \Carbon\Carbon::today()->toDateString());
?>


<?php $__env->startSection('title', 'Trial Balance'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Trial Balance</h1>
        <button onclick="window.print()" class="btn btn-secondary shadow-sm no-print">
            <i class="fas fa-print fa-sm text-white-50"></i> Print Report
        </button>
    </div>

    <div class="card shadow mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('reports.trial-balance')); ?>" class="form-inline">
                <label class="mr-2 font-weight-bold">As of Date:</label>
                <input type="date" name="end_date" class="form-control mr-3" value="<?php echo e($endDate); ?>">
                <button type="submit" class="btn btn-primary">Generate</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary text-center text-uppercase">
                Trial Balance as of <?php echo e(\Carbon\Carbon::parse($endDate)->format('F d, Y')); ?>

            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped" width="100%" cellspacing="0">
                    <thead class="bg-dark text-white text-center">
                        <tr>
                            <th class="text-left" style="width: 40%;">Account Name</th>
                            <th style="width: 20%;">Total Debit (<?php echo e($currency); ?>)</th>
                            <th style="width: 20%;">Total Credit (<?php echo e($currency); ?>)</th>
                            <th style="width: 20%;">Closing Balance (<?php echo e($currency); ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $grandTotalDr = 0; 
                            $grandTotalCr = 0;
                            $groupedAccounts = $accounts->groupBy('group');
                        ?>

                        <?php $__empty_1 = true; $__currentLoopData = ['Assets', 'Liabilities', 'Equity', 'Income', 'Expenses']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php if(isset($groupedAccounts[$groupName])): ?>
                                
                                <tr class="bg-light">
                                    <td colspan="4" class="font-weight-bold text-uppercase text-primary">
                                        <i class="fas fa-folder-open me-2"></i> <?php echo e($groupName); ?>

                                    </td>
                                </tr>

                                <?php 
                                    $groupDr = 0; $groupCr = 0; $groupBal = 0;
                                ?>

                                <?php $__currentLoopData = $groupedAccounts[$groupName]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $dr = $acc->debit ?? 0;
                                        $cr = $acc->credit ?? 0;
                                        $grandTotalDr += $dr;
                                        $grandTotalCr += $cr;
                                        $groupDr += $dr;
                                        $groupCr += $cr;

                                        // Calculate Closing Balance based on Account Normal Balance
                                        if (in_array($groupName, ['Assets', 'Expenses'])) {
                                            $closingBal = $dr - $cr; // Debit Normal
                                        } else {
                                            $closingBal = $cr - $dr; // Credit Normal
                                        }
                                        $groupBal += $closingBal;
                                    ?>
                                    <tr>
                                        <td class="pl-4"><?php echo e($acc->name); ?></td>
                                        <td class="text-end"><?php echo e(number_format($dr)); ?></td>
                                        <td class="text-end"><?php echo e(number_format($cr)); ?></td>
                                        <td class="text-end font-weight-bold <?php echo e($closingBal < 0 ? 'text-danger' : ''); ?>">
                                            <?php echo e(number_format(abs($closingBal))); ?> <?php echo e($closingBal < 0 ? '(Negative)' : ''); ?>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                
                                
                                <tr class="bg-light font-weight-bold font-italic">
                                    <td class="text-end text-muted">Total <?php echo e($groupName); ?>:</td>
                                    <td class="text-end text-muted"><?php echo e(number_format($groupDr)); ?></td>
                                    <td class="text-end text-muted"><?php echo e(number_format($groupCr)); ?></td>
                                    <td class="text-end text-dark border-start"><?php echo e(number_format(abs($groupBal))); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No accounts found to generate Trial Balance.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-dark text-white font-weight-bold">
                        <tr>
                            <td class="text-end text-uppercase">Grand Totals:</td>
                            <td class="text-end border-end"><?php echo e($currency); ?> <?php echo e(number_format($grandTotalDr)); ?></td>
                            <td class="text-end border-end"><?php echo e($currency); ?> <?php echo e(number_format($grandTotalCr)); ?></td>
                            <td class="text-center">
                                <?php if(round($grandTotalDr) == round($grandTotalCr)): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Balanced</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Unbalanced by <?php echo e($currency); ?> <?php echo e(number_format(abs($grandTotalDr - $grandTotalCr))); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        #accordionSidebar, .topbar, .sidebar, .main-header { display: none !important; }
        body { background-color: #fff; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/trial-balance.blade.php ENDPATH**/ ?>