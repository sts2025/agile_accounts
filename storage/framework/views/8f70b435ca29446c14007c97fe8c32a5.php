

<?php $__env->startSection('title', 'Client Details - ' . $client->name); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Client Profile: <?php echo e($client->name); ?></h1>
        <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
        </a>
    </div>

    <div class="row">

        
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Client Details</h6>
                    <span class="badge badge-success">Active</span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img class="img-profile rounded-circle mb-2" src="https://ui-avatars.com/api/?name=<?php echo e(urlencode($client->name)); ?>&background=4e73df&color=ffffff" style="width: 100px; height: 100px;">
                        <h4 class="h5 font-weight-bold text-gray-800"><?php echo e($client->name); ?></h4>
                        <p class="text-muted mb-0">ID: #<?php echo e($client->id); ?></p>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="font-weight-bold text-secondary text-uppercase text-xs">Contact Info</h6>
                        <div class="pl-2">
                            <p class="mb-1"><strong>Phone:</strong> <a href="tel:<?php echo e($client->phone_number); ?>"><?php echo e($client->phone_number); ?></a></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo e($client->email ?? 'N/A'); ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?php echo e($client->address); ?></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="font-weight-bold text-secondary text-uppercase text-xs">Personal Info</h6>
                        <div class="pl-2">
                            <p class="mb-1"><strong>National ID:</strong> <?php echo e($client->national_id ?? 'N/A'); ?></p>
                            
                            <p class="mb-1"><strong>Occupation:</strong> <?php echo e($client->business_occupation ?? 'N/A'); ?></p>
                            <p class="mb-1"><strong>Date of Birth:</strong> <?php echo e($client->date_of_birth ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <hr>
                    
                    <div class="d-flex justify-content-center">
                        <a href="<?php echo e(route('clients.edit', $client)); ?>" class="btn btn-primary btn-icon-split btn-sm mr-2">
                            <span class="icon text-white-50"><i class="fas fa-edit"></i></span>
                            <span class="text">Edit Profile</span>
                        </a>
                        
                        <a href="#" class="btn btn-info btn-icon-split btn-sm">
                            <span class="icon text-white-50"><i class="fas fa-book"></i></span>
                            <span class="text">View Ledger</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-7">
            
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Active Loans</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($client->loans->where('status', '!=', 'paid')->count()); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Paid Loans</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($client->loans->where('status', 'paid')->count()); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-gray-800">Loan History</h6>
                    <a href="<?php echo e(route('loans.create', ['client_id' => $client->id])); ?>" class="btn btn-sm btn-success shadow-sm">
                        <i class="fas fa-plus fa-sm text-white-50"></i> New Loan
                    </a>
                </div>
                <div class="card-body">
                    <?php if($client->loans->count() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="loansTable" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>Principal</th>
                                        <th>Date Given</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $client->loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e(route('loans.show', $loan->id)); ?>" class="font-weight-bold text-primary">
                                                #<?php echo e($loan->id); ?>

                                            </a>
                                        </td>
                                        <td><?php echo e(number_format($loan->principal_amount)); ?></td>
                                        <td><?php echo e($loan->created_at->format('d M Y')); ?></td>
                                        <td>
                                            <?php if($loan->status === 'paid'): ?>
                                                <span class="badge badge-success px-2 py-1">Paid</span>
                                            <?php elseif($loan->status === 'approved'): ?>
                                                <span class="badge badge-info px-2 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning px-2 py-1"><?php echo e(ucfirst($loan->status)); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo e(route('loans.show', $loan->id)); ?>" class="btn btn-info btn-circle btn-sm" title="View Loan">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">No loans found for this client.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/clients/show.blade.php ENDPATH**/ ?>