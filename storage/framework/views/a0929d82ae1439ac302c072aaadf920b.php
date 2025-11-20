

<?php $__env->startSection('title', 'Add New Client'); ?>

<?php $__env->startSection('content'); ?>
    <div class="card">
        <div class="card-header">
            <h4>Add a New Client</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo e(route('clients.store')); ?>">
                <?php echo csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone_number" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Business / Occupation (Optional)</label>
                    <input type="text" class="form-control" name="business_occupation">
                </div>

                <button type="submit" class="btn btn-primary">Save Client</button>
                <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/clients/create.blade.php ENDPATH**/ ?>