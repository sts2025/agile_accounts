
<?php $__env->startSection('title', 'Company Profile'); ?>
<?php $__env->startSection('content'); ?>
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Company Profile & Personal Settings</h1>
        </div>
        <div class="card-body">
            <?php if(session('status')): ?>
                <div class="alert alert-success"><?php echo e(session('status')); ?></div>
            <?php endif; ?>
            
            <!-- CRUCIAL FIX: Added <?php echo method_field('PATCH'); ?> and ensured enctype is present -->
            <form method="POST" action="<?php echo e(route('profile.update')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>

                <h5 class="mb-3 text-primary">Personal Details</h5>
                <div class="row">
                    <!-- Assuming User details (Name, Email, Phone, Address) are accessible via the $manager->user relationship -->
                    
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e(old('name', $manager->user->name ?? '')); ?>" required>
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email', $manager->user->email ?? '')); ?>" required>
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Personal Phone</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo e(old('phone_number', $manager->user->phone_number ?? '')); ?>" required>
                        <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo e(old('address', $manager->user->address ?? '')); ?>" required>
                        <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <h5 class="mt-4 mb-3 text-primary">Company Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo e(old('company_name', $manager->company_name)); ?>">
                        <?php $__errorArgs = ['company_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="company_phone" class="form-label">Company Phone</label>
                        <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo e(old('company_phone', $manager->company_phone)); ?>">
                        <?php $__errorArgs = ['company_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="currency_symbol" class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo e(old('currency_symbol', $manager->currency_symbol)); ?>" required>
                        <?php $__errorArgs = ['currency_symbol'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <h5 class="mt-4 mb-3 text-primary">Logo Upload</h5>
                <div class="mb-4">
                    <label for="company_logo" class="form-label">Company Logo (Max 2MB, JPG/PNG/GIF)</label>
                    <input class="form-control" type="file" id="company_logo" name="company_logo">
                    <?php $__errorArgs = ['company_logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <?php if($manager->company_logo_path): ?>
                    <div class="mb-4">
                        <p><strong>Current Logo:</strong></p>
                        <img src="<?php echo e(asset('storage/' . $manager->company_logo_path)); ?>" alt="Current Logo" class="img-thumbnail" style="max-height: 80px;">
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/profile/edit.blade.php ENDPATH**/ ?>