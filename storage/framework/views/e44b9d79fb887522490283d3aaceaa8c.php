

<?php $__env->startSection('title', 'Manage Managers'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Manage Loan Managers</h1>
    <p class="mb-4">Activate pending accounts, set currencies, suspend access, or delete managers.</p>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if(session('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <?php echo e(session('warning')); ?>

            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Registered Accounts</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 15%">Name</th>
                            <th style="width: 20%">Email</th>
                            <th style="width: 10%">Status</th>
                            <th style="width: 10%">Currency</th>
                            <th style="width: 20%">Support Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php 
                            $lm = $user->loanManager; 
                            // Check active status by presence of currency_symbol
                            $isActive = $lm && !empty($lm->currency_symbol);
                        ?>
                        <tr>
                            
                            <td class="align-middle font-weight-bold text-dark"><?php echo e($user->name); ?></td>
                            
                            
                            <td class="align-middle"><?php echo e($user->email); ?></td>
                            
                            
                            <td class="align-middle">
                                <?php if($isActive): ?>
                                    <span class="badge badge-success px-2 py-1">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary px-2 py-1">Inactive</span>
                                <?php endif; ?>
                            </td>

                            
                            <?php if($isActive): ?>
                                <td class="align-middle"><?php echo e($lm->currency_symbol); ?></td>
                                <td class="align-middle"><?php echo e($lm->support_phone); ?></td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        
                                        <a href="<?php echo e(route('admin.users.impersonate', $user->id)); ?>" class="btn btn-info btn-sm text-white mr-1 shadow-sm">
                                            Login As
                                        </a>

                                        
                                        <form action="<?php echo e(route('admin.managers.suspend', $user->id)); ?>" method="POST" class="mr-1">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-warning btn-sm text-dark shadow-sm">Suspend</button>
                                        </form>

                                        
                                        <form action="<?php echo e(route('admin.managers.destroy', $user->id)); ?>" method="POST" onsubmit="return confirm('PERMANENT DELETE: Are you sure?');">
                                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-danger btn-sm shadow-sm" title="Delete Permanently">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        
                                        <button type="button" class="btn btn-link btn-sm text-primary ml-1" data-toggle="modal" data-target="#edit<?php echo e($user->id); ?>" title="Edit Settings">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>

                                    
                                    <div class="modal fade" id="edit<?php echo e($user->id); ?>" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="<?php echo e(route('admin.managers.update', $user->id)); ?>" method="POST">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Manager</h5>
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Currency</label>
                                                            <select name="currency" class="form-control">
                                                                <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <option value="<?php echo e($curr); ?>" <?php echo e($lm->currency_symbol == $curr ? 'selected' : ''); ?>><?php echo e($curr); ?></option>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Phone</label>
                                                            <input type="text" name="support_phone" class="form-control" value="<?php echo e($lm->support_phone); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                            
                            <?php else: ?>
                                <form action="<?php echo e(route('admin.managers.activate', $user->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <td class="align-middle p-2">
                                        <select name="currency" class="form-control form-control-sm">
                                            <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($curr); ?>"><?php echo e($curr); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </td>
                                    <td class="align-middle p-2">
                                        <input type="text" name="support_phone" class="form-control form-control-sm" placeholder="Phone...">
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            
                                            <button type="submit" class="btn btn-success btn-sm mr-2 shadow-sm">
                                                Activate
                                            </button>

                                            
                                            <button type="submit" form="del<?php echo e($user->id); ?>" class="btn btn-danger btn-sm shadow-sm" title="Delete Request">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                                
                                <form id="del<?php echo e($user->id); ?>" action="<?php echo e(route('admin.managers.destroy', $user->id)); ?>" method="POST" class="d-none" onsubmit="return confirm('Delete this user?');">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                </form>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/admin/managers/index.blade.php ENDPATH**/ ?>