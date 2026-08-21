<?php $__env->startSection('title', 'Open Share Account'); ?>

<?php $__env->startPush('styles'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Open Share Account</h1>
        <a href="<?php echo e(route('mfi.shares.index')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Share Accounts
        </a>
    </div>

    <?php if($products->isEmpty()): ?>
        <div class="alert alert-warning shadow-sm border-start border-warning border-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            You need at least one active Share Product before you can open share accounts.
            <a href="<?php echo e(route('mfi.products.create', ['type' => 'shares'])); ?>" class="fw-bold">Create one now</a>.
        </div>
    <?php else: ?>
    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-plus-circle me-2"></i>New Share Account</h6>
        </div>
        <div class="card-body p-4 bg-light">
            <?php if($errors->any()): ?>
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('mfi.shares.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Select Client</label>
                    <select name="client_id" id="clientSelect" class="form-select shadow-sm" style="width: 100%;" required>
                        <option value="" disabled selected>Search for a registered client...</option>
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($client->id); ?>"><?php echo e($client->name); ?> (<?php echo e($client->phone_number); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Share Product</label>
                    <select name="mfi_product_id" class="form-select shadow-sm" required>
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?> — <?php echo e(number_format($product->share_value)); ?> / share</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Number of Shares to Buy Now</label>
                    <input type="number" step="0.01" min="0" name="units" class="form-control shadow-sm" value="0">
                    <small class="text-muted mt-2 d-block">You can leave this at 0 and buy shares later from the account page.</small>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Open Account
                    </button>
                    <a href="<?php echo e(route('mfi.shares.index')); ?>" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#clientSelect').select2({
            placeholder: "Search for a client...",
            allowClear: true
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/shares/create.blade.php ENDPATH**/ ?>