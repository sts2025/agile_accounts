

<?php $__env->startSection('title', 'Add New Client'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Register New Client</h1>
        <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Clients
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 800px;">
        <div class="card-body p-4">

            <?php if($errors->any()): ?>
                <div class="alert alert-danger shadow-sm border-start border-danger border-4">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('clients.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required placeholder="John Doe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo e(old('phone_number')); ?>" required placeholder="0700...">
                    </div>
                </div>

                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">National ID (NIN) <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="national_id" class="form-control" value="<?php echo e(old('national_id')); ?>" placeholder="e.g. CM12345678">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Date of Birth <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo e(old('date_of_birth')); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">Address / Location</label>
                    <textarea name="address" class="form-control" rows="2" required placeholder="Physical address of the client"><?php echo e(old('address')); ?></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Business / Occupation <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="business_occupation" class="form-control" value="<?php echo e(old('business_occupation')); ?>" placeholder="e.g. Shop Owner">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Email Address <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" placeholder="client@example.com">
                    </div>
                </div>

                <div class="d-flex gap-2 border-top pt-4">
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                        <i class="fas fa-user-check me-2"></i> Register Client
                    </button>
                    <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-light fw-bold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/clients/create.blade.php ENDPATH**/ ?>