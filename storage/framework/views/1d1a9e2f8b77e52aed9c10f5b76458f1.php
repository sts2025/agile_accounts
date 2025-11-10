
<?php $__env->startSection('title', 'Print Forms'); ?>

<?php $__env->startSection('content'); ?>
    <h1 class="h3 mb-4 text-gray-800">Print Forms</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select a Loan Agreement</h6>
        </div>
        <div class="card-body">
            <p>Select a client from the list to see their available loan agreements to print.</p>

            <div class="accordion" id="clientLoansAccordion">
                
                <?php $__empty_1 = true; $__currentLoopData = $clientsWithLoans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?php echo e($client->id); ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo e($client->id); ?>" aria-expanded="false" aria-controls="collapse-<?php echo e($client->id); ?>">
                                <strong><?php echo e($client->name); ?></strong> (<?php echo e($client->loans->count()); ?> Loan(s))
                            </button>
                        </h2>
                        <div id="collapse-<?php echo e($client->id); ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo e($client->id); ?>" data-bs-parent="#clientLoansAccordion">
                            <div class="accordion-body">
                                <ul class="list-group">
                                    <?php $__currentLoopData = $client->loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                Loan #<?php echo e($loan->id); ?> - UGX <?php echo e(number_format($loan->principal_amount)); ?>

                                                <small class="d-block text-muted">Date: <?php echo e(Carbon\Carbon::parse($loan->start_date)->format('M d, Y')); ?> | Status: <span class="badge bg-secondary"><?php echo e($loan->status); ?></span></Fsmall>
                                            </div>
                                            <a href="<?php echo e(route('loans.downloadAgreement', $loan->id)); ?>" class="btn btn-primary btn-sm" target="_blank">
                                                <i class="fas fa-print me-1"></i> Print
                                            </a>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="alert alert-info">
                        No clients with loans were found.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/print-forms.blade.php ENDPATH**/ ?>