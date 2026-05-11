

<?php $__env->startSection('title', 'Print Forms & Agreements'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Print Forms</h1>
    </div>

    
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header py-3 bg-white border-bottom">
            <h6 class="m-0 fw-bold text-primary">Search Client Agreement</h6>
        </div>
        <div class="card-body">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-primary text-white border-primary">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="clientSearchInput" class="form-control border-primary" placeholder="Type client name here to find their forms..." autofocus>
            </div>
            <small class="text-muted mt-2 d-block">Filter through <?php echo e($clientsWithLoans->count()); ?> clients with registered loans.</small>
        </div>
    </div>

    
    <div class="accordion shadow-sm" id="clientLoansAccordion">
        <?php $__empty_1 = true; $__currentLoopData = $clientsWithLoans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="card client-item border-0 mb-1" data-name="<?php echo e(strtolower($client->name)); ?>">
                <div class="card-header p-0 bg-white" id="heading-<?php echo e($client->id); ?>">
                    <h2 class="mb-0">
                        
                        <button class="btn btn-link btn-block text-start text-dark fw-bold py-3 px-4 d-flex justify-content-between align-items-center w-100 text-decoration-none" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse-<?php echo e($client->id); ?>" 
                                aria-expanded="false" 
                                aria-controls="collapse-<?php echo e($client->id); ?>">
                            <span>
                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                <?php echo e($client->name); ?> 
                                <span class="badge bg-secondary ms-2 rounded-pill"><?php echo e($client->loans->count()); ?> Loan(s)</span>
                            </span>
                            <i class="fas fa-chevron-down fa-sm text-secondary"></i>
                        </button>
                    </h2>
                </div>

                
                <div id="collapse-<?php echo e($client->id); ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo e($client->id); ?>" data-bs-parent="#clientLoansAccordion">
                    <div class="card-body bg-light">
                        <ul class="list-group">
                            <?php $__currentLoopData = $client->loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center shadow-sm mb-2 border-start border-4 border-primary">
                                    <div>
                                        <div class="fw-bold text-primary">
                                            Loan #<?php echo e($loan->reference_id ?? $loan->id); ?>

                                        </div>
                                        <div class="small text-muted">
                                            Principal: <strong><?php echo e(number_format($loan->principal_amount)); ?></strong> | 
                                            Date: <?php echo e(\Carbon\Carbon::parse($loan->start_date)->format('d M, Y')); ?>

                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        
                                        <a href="<?php echo e(route('loans.downloadAgreement', $loan->id)); ?>" class="btn btn-primary btn-sm px-3 shadow-sm" target="_blank">
                                            <i class="fas fa-print me-1"></i> Print
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="alert alert-info shadow-sm text-center p-4">
                <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                No clients with loans were found.
            </div>
        <?php endif; ?>
    </div>

    
    <div id="noResults" class="text-center p-5 d-none">
        <i class="fas fa-search-minus fa-3x text-gray-300 mb-3"></i>
        <h5 class="text-secondary">No matching clients found.</h5>
    </div>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('clientSearchInput');
        const clientItems = document.querySelectorAll('.client-item');
        const noResults = document.getElementById('noResults');
        const accordion = document.getElementById('clientLoansAccordion');

        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            let hasResults = false;

            clientItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(query)) {
                    item.classList.remove('d-none');
                    hasResults = true;
                } else {
                    item.classList.add('d-none');
                }
            });

            if (!hasResults && query !== '') {
                noResults.classList.remove('d-none');
                accordion.classList.add('d-none');
            } else {
                noResults.classList.add('d-none');
                accordion.classList.remove('d-none');
            }
        });
    });
</script>
<style>
    .client-item .btn-link:hover {
        background-color: rgba(0,0,0,0.02);
    }
    .client-item .btn-link:focus {
        box-shadow: none;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/print-forms.blade.php ENDPATH**/ ?>