

<?php $__env->startSection('title', 'Open Savings Account'); ?>

<?php $__env->startPush('styles'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Open Savings Account</h1>
        <a href="<?php echo e(route('mfi.savings.index')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Accounts
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-plus-circle me-2"></i>New Account Details</h6>
        </div>
        <div class="card-body p-4 bg-light">

            
            <?php if(session('error')): ?>
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>System Error:</strong><br>
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            
            <?php if($errors->any()): ?>
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('mfi.savings.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Select Client</label>
                    <select name="client_id" id="clientSelect" class="form-select shadow-sm" style="width: 100%;" required>
                        <option value="" disabled selected>Search for a registered client...</option>
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($client->id); ?>"><?php echo e($client->name); ?> (<?php echo e($client->phone_number); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i> Client must be registered in the system first.
                    </small>
                </div>

                
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Account Type</label>
                    <?php if($products->isNotEmpty()): ?>
                        <select name="mfi_product_id" class="form-select shadow-sm">
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($product->id); ?>" <?php echo e(old('mfi_product_id') == $product->id ? 'selected' : ''); ?>>
                                    <?php echo e($product->name); ?>

                                    <?php if($product->is_compulsory): ?> (Compulsory) <?php endif; ?>
                                    <?php if($product->minimum_balance > 0): ?> — min balance <?php echo e(number_format($product->minimum_balance)); ?> <?php endif; ?>
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <small class="text-muted mt-2 d-block">
                            <a href="<?php echo e(route('mfi.products.index')); ?>">Manage savings products</a>
                        </small>
                    <?php else: ?>
                        <input type="text" class="form-control bg-white shadow-sm fw-bold text-success" value="Standard Savings Account" readonly>
                        <small class="text-muted mt-2 d-block">
                            No custom savings products configured yet. <a href="<?php echo e(route('mfi.products.create', ['type' => 'savings'])); ?>">Set one up</a> to control interest, minimum balance, etc.
                        </small>
                    <?php endif; ?>
                </div>

                
                <div class="card border-success border-2 shadow-sm mb-4">
                    <div class="card-body">
                        <label class="form-label fw-bold text-success text-uppercase mb-2">Initial Cash Deposit (Opening Balance)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-success text-white border-success font-weight-bold">
                                <?php echo e(Auth::user()->getCompany()->currency_symbol ?? 'UGX'); ?>

                            </span>
                            <input type="number" step="any" name="opening_balance" class="form-control text-end fw-bold font-monospace fs-4 text-dark border-success" value="0" min="0" required>
                        </div>
                        <small class="text-muted mt-2 d-block">How much cash is the client depositing right now to open the account?</small>
                    </div>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Open Account & Record Deposit
                    </button>
                    <a href="<?php echo e(route('mfi.savings.index')); ?>" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
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
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/savings/create.blade.php ENDPATH**/ ?>