

<?php $__env->startSection('title', 'Edit Loan'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edit Loan for: <?php echo e($loan->client->name); ?></h1>
            <hr>

            <form method="POST" action="<?php echo e(route('loans.update', $loan->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="mb-3">
                    <label for="principal_amount" class="form-label">Principal Amount (UGX)</label>
                    <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" value="<?php echo e($loan->principal_amount); ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" value="<?php echo e($loan->interest_rate); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="term" class="form-label">Term (in months)</label>
                        <input type="number" class="form-control" id="term" name="term" value="<?php echo e($loan->term); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="start_date" class="form-label">Loan Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo e($loan->start_date); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Loan Status</label>
                    <select class="form-select" name="status">
                        <option value="pending" <?php echo e($loan->status == 'pending' ? 'selected' : ''); ?>>Pending</option>
                        <option value="active" <?php echo e($loan->status == 'active' ? 'selected' : ''); ?>>Active</option>
                        <option value="paid" <?php echo e($loan->status == 'paid' ? 'selected' : ''); ?>>Paid</option>
                        <option value="defaulted" <?php echo e($loan->status == 'defaulted' ? 'selected' : ''); ?>>Defaulted</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Loan</button>
                <a href="<?php echo e(route('loans.show', $loan->id)); ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/loans/edit.blade.php ENDPATH**/ ?>