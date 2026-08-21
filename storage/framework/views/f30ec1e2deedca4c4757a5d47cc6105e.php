<?php $__env->startSection('title', 'Share Account Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold">
                Account: <span class="font-monospace text-primary"><?php echo e($account->account_number); ?></span>
            </h1>
            <p class="mb-0 text-muted">Client: <strong><?php echo e($account->client->name); ?></strong> (<?php echo e($account->client->phone_number); ?>)</p>
        </div>
        <a href="<?php echo e(route('mfi.shares.index')); ?>" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Share Accounts
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
            <i class="fas fa-exclamation-circle me-2"></i> <strong>Failed:</strong>
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
            <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
                <div class="card-body py-4 text-center">
                    <h6 class="text-uppercase text-muted fw-bold mb-3">Units Held</h6>
                    <h2 class="display-5 fw-bold text-warning font-monospace mb-1">
                        <?php echo e(rtrim(rtrim(number_format($account->units, 4), '0'), '.')); ?>

                    </h2>
                    <p class="text-muted small mb-4">
                        Value: <?php echo e(optional(Auth::user()->getCompany())->currency_symbol ?? 'UGX'); ?> <?php echo e(number_format($account->balance)); ?>

                        <?php if($product): ?>
                            (<?php echo e(number_format($product->share_value)); ?> / share)
                        <?php endif; ?>
                    </p>

                    <div class="d-grid gap-3 mt-2">
                        <button class="btn btn-success btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#buyModal">
                            <i class="fas fa-plus me-2"></i> Buy Shares
                        </button>
                        <button class="btn btn-outline-danger btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#redeemModal">
                            <i class="fas fa-minus me-2"></i> Redeem Shares
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-2"></i> Share Transaction History</h6>
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
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $account->transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?php echo e(\Carbon\Carbon::parse($tx->created_at)->format('d M, Y H:i')); ?></td>
                                    <td>
                                        <?php if($tx->transaction_type == 'share_purchase'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="fas fa-plus me-1"></i> Purchase</span>
                                        <?php elseif($tx->transaction_type == 'share_redemption'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1"><i class="fas fa-minus me-1"></i> Redemption</span>
                                        <?php elseif($tx->transaction_type == 'dividend'): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><i class="fas fa-gift me-1"></i> Dividend</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1"><?php echo e(ucfirst($tx->transaction_type)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo e($tx->narration ?? '—'); ?></td>
                                    <td class="text-end pe-4 fw-bold font-monospace <?php echo e($tx->transaction_type == 'share_redemption' ? 'text-danger' : 'text-success'); ?>">
                                        <?php echo e($tx->transaction_type == 'share_redemption' ? '-' : '+'); ?> <?php echo e(number_format($tx->amount)); ?>

                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No transactions found.</td>
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


<div class="modal fade" id="buyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?php echo e(route('mfi.shares.buy', $account->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Buy Shares</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <?php if($product): ?>
                        <div class="alert alert-info py-2 small">Price per share: <?php echo e(number_format($product->share_value)); ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Number of Shares</label>
                        <input type="number" step="0.01" min="0.01" name="units" class="form-control form-control-lg text-end fw-bold" required>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Confirm Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="redeemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?php echo e(route('mfi.shares.redeem', $account->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Redeem Shares</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-info py-2 small">
                        Units held: <?php echo e(rtrim(rtrim(number_format($account->units, 4), '0'), '.')); ?>

                        <?php if($product): ?> &middot; Price per share: <?php echo e(number_format($product->share_value)); ?> <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Number of Shares to Redeem</label>
                        <input type="number" step="0.01" min="0.01" max="<?php echo e($account->units); ?>" name="units" class="form-control form-control-lg text-end fw-bold" required>
                    </div>
                    <small class="text-muted d-block">Redeeming pays out cash at the current share value and reduces the member's holding.</small>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">Confirm Redemption</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/mfi/shares/show.blade.php ENDPATH**/ ?>