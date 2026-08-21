<?php $__env->startSection('title', 'Fixed Deposits'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-lock text-info me-2"></i> Fixed Deposits</h1>
        <a href="<?php echo e(route('mfi.fixed-deposits.create')); ?>" class="btn btn-success fw-bold shadow-sm">
            <i class="fas fa-plus me-2"></i> Open Fixed Deposit
        </a>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">All Fixed Deposits</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Account Number</th>
                            <th>Client Name</th>
                            <th class="text-end">Principal</th>
                            <th class="text-center">Maturity Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $matured = $account->maturity_date && \Carbon\Carbon::parse($account->maturity_date)->isPast();
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-primary"><?php echo e($account->account_number); ?></td>
                            <td class="fw-bold text-dark"><?php echo e($account->client->name ?? 'Unknown Client'); ?></td>
                            <td class="text-end font-monospace"><?php echo e(number_format($account->principal_amount)); ?></td>
                            <td class="text-center small">
                                <?php echo e($account->maturity_date ? \Carbon\Carbon::parse($account->maturity_date)->format('d M, Y') : 'N/A'); ?>

                            </td>
                            <td class="text-center">
                                <?php if($account->status == 'closed'): ?>
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Closed</span>
                                <?php elseif($matured): ?>
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Matured</span>
                                <?php else: ?>
                                    <span class="badge bg-info px-3 py-1 rounded-pill">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?php echo e(route('mfi.fixed-deposits.show', $account->id)); ?>" class="btn btn-sm btn-outline-primary shadow-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-lock fa-3x mb-3 text-light"></i>
                                <h5>No fixed deposits opened yet.</h5>
                                <a href="<?php echo e(route('mfi.fixed-deposits.create')); ?>" class="btn btn-sm btn-outline-primary mt-2">Open First Deposit</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/fixed-deposits/index.blade.php ENDPATH**/ ?>