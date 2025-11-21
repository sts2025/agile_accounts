<?php
// Retrieve the dynamic currency symbol from the authenticated user's manager settings
$currency = optional(auth()->user()->manager)->currency_symbol ?? 'UGX';
?>


<?php $__env->startSection('title', 'Create New Loan'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Create a New Loan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Loan Details</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo e(route('loans.store')); ?>">
                <?php echo csrf_field(); ?>
                
                
                <div class="form-group mb-3">
                    <label for="client_id">Select Client <span class="text-danger">*</span></label>
                    <select class="form-control" id="client_id" name="client_id" required>
                        <option value="">-- Please choose a client --</option>
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($client->id); ?>" <?php echo e(old('client_id') == $client->id ? 'selected' : ''); ?>>
                                <?php echo e($client->name); ?> (<?php echo e($client->phone ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['client_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        
                        <label for="principal_amount">Principal Amount (<?php echo e($currency); ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="principal_amount" name="principal_amount" value="<?php echo e(old('principal_amount')); ?>" required>
                        <?php $__errorArgs = ['principal_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        
                        <label for="processing_fee">Processing Fee (<?php echo e($currency); ?>)</label>
                        <input type="number" step="0.01" class="form-control" id="processing_fee" name="processing_fee" value="<?php echo e(old('processing_fee', 0)); ?>">
                        <?php $__errorArgs = ['processing_fee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="interest_rate">Interest Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" value="<?php echo e(old('interest_rate')); ?>" required>
                        <?php $__errorArgs = ['interest_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="term">Loan Term (Periods) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="term" name="term" value="<?php echo e(old('term')); ?>" placeholder="e.g., 3, 6, 12" required>
                        <?php $__errorArgs = ['term'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="repayment_frequency">Frequency <span class="text-danger">*</span></label>
                        <select class="form-control" id="repayment_frequency" name="repayment_frequency" required>
                            <option value="Monthly" <?php echo e(old('repayment_frequency') == 'Monthly' ? 'selected' : ''); ?>>Monthly</option>
                            <option value="Weekly" <?php echo e(old('repayment_frequency') == 'Weekly' ? 'selected' : ''); ?>>Weekly</option>
                            <option value="Daily" <?php echo e(old('repayment_frequency') == 'Daily' ? 'selected' : ''); ?>>Daily</option>
                        </select>
                        <?php $__errorArgs = ['repayment_frequency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="start_date">Loan Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo e(old('start_date', date('Y-m-d'))); ?>" required>
                    <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <hr>

                
                <h4 class="mb-3 mt-4 text-secondary">Guarantor Details (Optional)</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_first_name">First Name</label>
                        <input type="text" class="form-control" id="guarantor_first_name" name="guarantor_first_name" value="<?php echo e(old('guarantor_first_name')); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_last_name">Last Name</label>
                        <input type="text" class="form-control" id="guarantor_last_name" name="guarantor_last_name" value="<?php echo e(old('guarantor_last_name')); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_phone_number">Phone Number</label>
                        <input type="text" class="form-control" id="guarantor_phone_number" name="guarantor_phone_number" value="<?php echo e(old('guarantor_phone_number')); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guarantor_occupation">Occupation</label>
                        <input type="text" class="form-control" id="guarantor_occupation" name="guarantor_occupation" value="<?php echo e(old('guarantor_occupation')); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="guarantor_address">Address</label>
                    <input type="text" class="form-control" id="guarantor_address" name="guarantor_address" value="<?php echo e(old('guarantor_address')); ?>">
                </div>
                <div class="mb-3">
                    <label for="guarantor_relationship">Relationship to Client</label>
                    <input type="text" class="form-control" id="guarantor_relationship" name="guarantor_relationship" value="<?php echo e(old('guarantor_relationship')); ?>" placeholder="e.g., Brother, Friend, Co-worker">
                </div>
                <hr>
                
                
                <h4 class="mb-3 mt-4 text-secondary">Collateral Details (Optional)</h4>
                <div class="mb-3">
                    <label for="collateral_type">Type of Collateral</label>
                    <input type="text" class="form-control" id="collateral_type" name="collateral_type" value="<?php echo e(old('collateral_type')); ?>" placeholder="e.g., Land Title, Vehicle Logbook">
                </div>
                <div class="mb-3">
                    <label for="collateral_description">Description</label>
                    <textarea class="form-control" id="collateral_description" name="collateral_description" rows="2" placeholder="e.g., Toyota Corolla, Reg No. UBA 123X"><?php echo e(old('collateral_description')); ?></textarea>
                </div>
                <div class="mb-3">
                    
                    <label for="collateral_valuation_amount">Valuation Amount (<?php echo e($currency); ?>)</label>
                    <input type="number" step="0.01" class="form-control" id="collateral_valuation_amount" name="collateral_valuation_amount" value="<?php echo e(old('collateral_valuation_amount')); ?>">
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-icon-split">
                        <span class="icon text-white-50"><i class="fas fa-save"></i></span>
                        <span class="text">Save New Loan</span>
                    </button>
                    <a href="<?php echo e(route('loans.index')); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/loans/create.blade.php ENDPATH**/ ?>