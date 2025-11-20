
<?php $__env->startSection('title', 'Company Profile'); ?>
<?php $__env->startSection('content'); ?>
    <div class="card">
        <div class="card-header"><h1>Company Profile & Settings</h1></div>
        <div class="card-body">
            <?php if(session('status')): ?>
                <div class="alert alert-success"><?php echo e(session('status')); ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('profile.update')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo e(old('company_name', $manager->company_name)); ?>">
                </div>
                <div class="mb-3">
                    <label for="company_phone" class="form-label">Company Phone</label>
                    <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo e(old('company_phone', $manager->company_phone)); ?>">
                </div>
                <div class="mb-4">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input class="form-control" type="file" id="company_logo" name="company_logo">
                </div>
                <?php if($manager->company_logo_path): ?>
                    <div class="mb-4">
                        <p><strong>Current Logo:</strong></p>
                        <img src="<?php echo e(asset('storage/' . $manager->company_logo_path)); ?>" alt="Current Logo" style="max-height: 80px;">
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/profile/edit.blade.php ENDPATH**/ ?>