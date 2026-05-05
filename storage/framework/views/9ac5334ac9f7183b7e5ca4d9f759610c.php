

<?php $__env->startSection('title', 'Account Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold">
                Account: <span class="font-monospace text-primary"><?php echo e($account->account_number); ?></span>
            </h1>
            <p class="mb-0 text-muted">Client: <strong><?php echo e($account->client->name); ?></strong> (<?php echo e($account->client->phone_number); ?>)</p>
        </div>
        <a href="<?php echo e(route('mfi.savings.index')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Accounts
        </a>
    </div>

    
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-circle me-2"></i> <strong>Transaction Failed:</strong>
            <ul class="mb-0 mt-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body py-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3 text-center">Account Balance</h6>
                    <div class="text-center mb-4">
                        <h2 class="display-5 fw-bold text-success font-monospace mb-1">
                            <?php echo e(optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX'); ?> <?php echo e(number_format($account->balance)); ?>

                        </h2>
                        
                        
                        <?php if($account->lien_amount > 0): ?>
                            <small class="text-danger d-block">
                                <i class="fas fa-lock"></i> Locked as Loan Security: UGX <?php echo e(number_format($account->lien_amount)); ?>

                            </small>
                            <small class="text-success fw-bold d-block mt-1">
                                Available to Withdraw: UGX <?php echo e(number_format($account->balance - $account->lien_amount)); ?>

                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-3 mt-4">
                        <button class="btn btn-success btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="fas fa-arrow-down me-2"></i> Deposit Cash
                        </button>
                        <button class="btn btn-warning btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="fas fa-arrow-up me-2"></i> Withdraw Cash
                        </button>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Passbook / Statement</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase sticky-top">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Type</th>
                                    <th>Ref/Method</th>
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $account->transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    
                                    <td class="ps-4 text-muted small"><?php echo e(\Carbon\Carbon::parse($tx->created_at)->format('d M, Y H:i')); ?></td>
                                    <td>
                                        <?php if($tx->type == 'deposit'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="fas fa-plus me-1"></i> Deposit</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1"><i class="fas fa-minus me-1"></i> Withdraw</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo e($tx->reference ?? 'N/A'); ?></td>
                                    <td class="text-muted small">Via Controller Logic</td>
                                    <td class="text-end pe-4 fw-bold font-monospace <?php echo e($tx->type == 'deposit' ? 'text-success' : 'text-danger'); ?>">
                                        <?php echo e($tx->type == 'deposit' ? '+' : '-'); ?> <?php echo e(number_format($tx->amount)); ?>

                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No transactions found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            
            <form action="<?php echo e(route('mfi.savings.deposit')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                
                <input type="hidden" name="savings_account_id" value="<?php echo e($account->id); ?>">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Deposit Cash</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Deposit</label>
                        <input type="number" step="any" name="amount" class="form-control form-control-lg text-end fw-bold font-monospace text-success border-success" required min="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Reference (Mobile Money ID, Receipt No.)</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. MTN-123456789">
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn-light btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Confirm Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            
            <form action="<?php echo e(route('mfi.savings.withdraw')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                
                <input type="hidden" name="savings_account_id" value="<?php echo e($account->id); ?>">
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">Withdraw Cash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-info py-2 small">
                        <strong>Available to Withdraw:</strong> <?php echo e(optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX'); ?> <?php echo e(number_format($account->balance - $account->lien_amount)); ?>

                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Withdraw</label>
                        
                        <input type="number" step="any" name="amount" class="form-control form-control-lg text-end fw-bold font-monospace text-danger border-warning" required min="1000" max="<?php echo e($account->balance - $account->lien_amount); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Reference / Narration</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. School fees withdrawal">
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn-light btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4">Confirm Withdrawal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/savings/show.blade.php ENDPATH**/ ?>