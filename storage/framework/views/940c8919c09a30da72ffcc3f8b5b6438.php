<?php $__env->startSection('title', 'End of Period'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-calendar-check text-primary me-2"></i> End of Period — Savings Interest</h1>
        <a href="<?php echo e(route('mfi.savings.index')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Savings
        </a>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-primary">Post Savings Interest</h6>
        </div>
        <div class="card-body p-4 bg-light">
            <p class="text-muted small">
                Calculates and credits interest owed to every active savings account since it was last posted,
                using each account's Product Settings interest rate (simple interest, actual days / 365).
                Accounts on a product with no interest rate configured are skipped. Fixed deposits are not
                affected here — their interest is settled in full when the deposit is closed.
            </p>

            <form action="<?php echo e(route('mfi.end-of-period.preview')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Post Interest As Of</label>
                    <input type="date" name="as_of_date" class="form-control" required
                           max="<?php echo e(date('Y-m-d')); ?>" value="<?php echo e(old('as_of_date', date('Y-m-d'))); ?>">
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-eye me-2"></i> Preview Interest Posting
                </button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/end-of-period/index.blade.php ENDPATH**/ ?>