<?php $__env->startSection('title', 'Preview Dividend Distribution'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-eye text-warning me-2"></i> Preview Dividend Distribution</h1>
        <a href="<?php echo e(route('mfi.dividends.create')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Start Over
        </a>
    </div>

    <div class="alert alert-info shadow-sm border-start border-info border-4">
        <strong>Pool:</strong> <?php echo e(optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX'); ?> <?php echo e(number_format($poolAmount)); ?>

        <?php if($description): ?> &middot; <?php echo e($description); ?> <?php endif; ?>
        &middot; <strong><?php echo e(rtrim(rtrim(number_format($totalUnits, 4), '0'), '.')); ?></strong> total units.
        Nothing has been paid out yet — review below, then confirm.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Proposed Payouts</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Client</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Payout</th>
                            <th class="text-center pe-4">Destination</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?php echo e($row->client_name); ?></td>
                            <td class="text-end font-monospace"><?php echo e(rtrim(rtrim(number_format($row->units, 4), '0'), '.')); ?></td>
                            <td class="text-end font-monospace fw-bold text-success"><?php echo e(number_format($row->payout)); ?></td>
                            <td class="text-center pe-4">
                                <?php if($row->has_savings): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success">Savings account</span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning">No savings account — will be skipped</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form action="<?php echo e(route('mfi.dividends.distribute')); ?>" method="POST" onsubmit="return confirm('This will move real money into member savings accounts and cannot be undone. Continue?');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="pool_amount" value="<?php echo e($poolAmount); ?>">
        <input type="hidden" name="description" value="<?php echo e($description); ?>">
        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
            <i class="fas fa-check-circle me-2"></i> Confirm &amp; Distribute
        </button>
        <a href="<?php echo e(route('mfi.dividends.create')); ?>" class="btn btn-light fw-bold">Cancel</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/dividends/preview.blade.php ENDPATH**/ ?>