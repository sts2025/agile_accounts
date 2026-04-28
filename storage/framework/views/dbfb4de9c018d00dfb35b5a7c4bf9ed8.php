<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>

<?php $__env->startSection('title', 'Balance Sheet'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mt-4" id="printable-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Balance Sheet</h1>
            <h5>As at: <?php echo e(\Carbon\Carbon::parse($reportDate)->format('d M, Y')); ?></h5>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>

    
    <form method="GET" action="<?php echo e(route('reports.balance-sheet')); ?>" class="mb-4 p-3 bg-light border rounded no-print">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="report_date">Report Date</label>
                <input type="date" name="report_date" id="report_date" class="form-control" value="<?php echo e($reportDate); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary w-100">Filter Report</button>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Assets</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $assets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($asset->name); ?></td>
                                <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($asset->balance, 0)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No Assets found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>Total Assets</td>
                                <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalAssets, 0)); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">Liabilities</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $liabilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $liability): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($liability->name); ?></td>
                                <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($liability->balance, 0)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No Liabilities found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>Total Liabilities</td>
                                <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalLiabilities, 0)); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">Equity</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $equity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($item->name); ?></td>
                                <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($item->balance, 0)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No Equity found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>Total Equity</td>
                                <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalEquity, 0)); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 offset-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">Total Liabilities & Equity</div>
                <div class="card-body">
                    <h4 class="text-end fw-bold"><?php echo e($currency); ?> <?php echo e(number_format($totalLiabilitiesAndEquity, 0)); ?></h4>
                    <?php if(abs($totalAssets - $totalLiabilitiesAndEquity) > 0.01): ?>
                        <p class="text-end text-danger fw-bold mt-2">
                            Note: Assets (<?php echo e($currency); ?> <?php echo e(number_format($totalAssets, 0)); ?>) do not equal L+E. This is due to missing Opening Balances.
                        </p>
                    <?php else: ?>
                          <p class="text-end text-success fw-bold mt-2">
                            Assets = Liabilities + Equity
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none; }
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/balance-sheet.blade.php ENDPATH**/ ?>