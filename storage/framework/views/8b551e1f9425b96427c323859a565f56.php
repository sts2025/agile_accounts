

<?php $__env->startSection('title', 'My Clients'); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Clients</h1>
        <a href="<?php echo e(route('clients.create')); ?>" class="btn btn-primary">Add New Client</a>
    </div>

    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Find a Client</div>
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('clients.index')); ?>">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name..." value="<?php echo e(request('search')); ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Business / Occupation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($client->name); ?></td>
                            <td><?php echo e($client->phone_number); ?></td>
                            <td><?php echo e($client->address); ?></td>
                            <td><?php echo e($client->business_occupation ?? 'N/A'); ?></td>
                            <td>
                                <a href="<?php echo e(route('clients.edit', $client->id)); ?>" class="btn btn-secondary btn-sm">Edit</a>

                                
                                <a href="<?php echo e(route('clients.ledger', $client->id)); ?>" class="btn btn-info btn-sm">Ledger</a>

                                <form method="POST" action="<?php echo e(route('clients.destroy', $client->id)); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="button" class="btn btn-sm btn-danger confirm-delete-btn"
                                     data-action="<?php echo e(route('clients.destroy', $client->id)); ?>"
                                     data-bs-toggle="modal" data-bs-target="#confirmActionModal">Delete
                                    </button>
                                </form>
                                
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center">No clients found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/clients/index.blade.php ENDPATH**/ ?>