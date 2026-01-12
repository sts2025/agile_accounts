

<?php $__env->startSection('title', 'Admin Dashboard'); ?>


<?php $__env->startSection('page_heading', 'Admin Panel'); ?> 

<?php $__env->startSection('content'); ?>
    
    <?php if(session('status')): ?>
        <div class="alert alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manage Loan Managers</h6>
            <p class="m-0 text-secondary">Activate or suspend managers and set currency/support contacts.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Currency</th>
                            <th>Support Phone</th>
                            <th class="text-center" style="min-width: 300px;">Actions / Settings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $loanManager = $manager->loanManager;
                                $isActive = $loanManager && $loanManager->is_active;
                            ?>
                            <tr>
                                <td><?php echo e($manager->name); ?></td>
                                <td><?php echo e($manager->email); ?></td>
                                <td>
                                    <?php if($isActive): ?>
                                        
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        
                                        <span class="badge badge-secondary">Inactive / Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($loanManager->currency_symbol ?? 'N/A'); ?></td>
                                <td><?php echo e($loanManager->support_phone ?? 'N/A'); ?></td>
                                
                                
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <?php if($isActive): ?>
                                            
                                            <a href="<?php echo e(route('admin.users.impersonate', $manager->id)); ?>" class="btn btn-info btn-sm mr-2">Login As</a>

                                            
                                            <a href="<?php echo e(route('admin.managers.suspend', $manager->id)); ?>" 
                                                class="btn btn-warning btn-sm"
                                                onclick="return confirm('Are you sure you want to suspend this manager?');">
                                                Suspend
                                            </a>
                                        <?php else: ?>
                                            
                                            
                                            <form method="POST" action="<?php echo e(route('admin.managers.update', $manager->id)); ?>" class="form-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PUT'); ?> 
                                                
                                                
                                                <input type="hidden" name="is_active" value="1">
                                                
                                                
                                                <select name="currency_symbol" class="form-control form-control-sm mr-2" style="width: 100px;" required>
                                                    <option value="" disabled selected>Currency</option>
                                                    <option value="UGX">UGX</option>
                                                    <option value="RWF">RWF</option>
                                                </select>
                                                
                                                
                                                <input type="text" name="support_phone" class="form-control form-control-sm mr-2" placeholder="Support Phone" style="width: 150px;" required>
                                                
                                                
                                                <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center">No loan managers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>