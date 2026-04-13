<?php
// Fetch dynamic currency once at the top
$currency = \App\Models\LoanManager::getCurrency() ?? 'UGX';
// Define default dates if not passed from controller
$startDate = $startDate ?? \Carbon\Carbon::now()->startOfMonth()->toDateString();
$endDate = $endDate ?? \Carbon\Carbon::now()->endOfMonth()->toDateString();
?>


<?php $__env->startSection('title', 'Payables & Receivables'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">Payables & Receivables</h1>
        <div>
            
            <button class="btn btn-success shadow-sm me-2 no-print" data-bs-toggle="modal" data-bs-target="#addPayableReceivableModal">
                <i class="fas fa-plus"></i> Add Entry
            </button>
            <button onclick="window.print()" class="btn btn-secondary shadow-sm no-print">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle me-2"></i> <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    
    <form method="GET" action="<?php echo e(route('cash-transactions.index')); ?>" class="mb-4 p-3 bg-light border rounded shadow-sm no-print">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label fw-bold text-muted small">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo e($startDate); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label fw-bold text-muted small">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo e($endDate); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-filter me-2"></i> Filter Report
                </button>
            </div>
        </div>
    </form>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-3" style="width: 15%;">Date</th>
                            <th style="width: 20%;">Type</th>
                            <th style="width: 45%;">Description</th>
                            <th class="text-end pe-3" style="width: 20%;">Amount (<?php echo e($currency); ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $totalPayables = 0;
                            $totalReceivables = 0;
                        ?>
                        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                // THE FIX: Now correctly identifies 'inflow' and 'outflow' from the database!
                                $typeCode = strtolower($transaction->type);
                                if (in_array($typeCode, ['p', 'payable', 'outflow'])) {
                                    $typeDisplay = 'Payable (Out)';
                                    $badgeClass = 'danger';
                                    $totalPayables += $transaction->amount;
                                } elseif (in_array($typeCode, ['r', 'receivable', 'inflow'])) {
                                    $typeDisplay = 'Receivable (In)';
                                    $badgeClass = 'success';
                                    $totalReceivables += $transaction->amount;
                                } else {
                                    $typeDisplay = ucfirst($typeCode);
                                    $badgeClass = 'secondary';
                                }
                            ?>
                        <tr>
                            <td class="ps-3"><?php echo e(\Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y')); ?></td>
                            <td><span class="badge bg-<?php echo e($badgeClass); ?> px-2 py-1"><?php echo e($typeDisplay); ?></span></td> 
                            <td class="fw-bold"><?php echo e($transaction->description ?? 'N/A'); ?></td>
                            <td class="text-end pe-3 font-monospace fw-bold text-<?php echo e($badgeClass); ?>">
                                <?php echo e(number_format($transaction->amount, 0)); ?>

                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">No payables or receivables found for the selected period.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr class="table-danger fw-bold">
                            <td colspan="3" class="text-end text-uppercase">Total Payables (Cash Out)</td>
                            <td class="text-end pe-3 text-danger font-monospace fs-5"><?php echo e($currency); ?> <?php echo e(number_format($totalPayables, 0)); ?></td>
                        </tr>
                        <tr class="table-success fw-bold">
                            <td colspan="3" class="text-end text-uppercase">Total Receivables (Cash In)</td>
                            <td class="text-end pe-3 text-success font-monospace fs-5"><?php echo e($currency); ?> <?php echo e(number_format($totalReceivables, 0)); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            
            <?php if(method_exists($transactions, 'links')): ?>
                <div class="p-3 border-top no-print">
                    <?php echo e($transactions->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="modal fade" id="addPayableReceivableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?php echo e(route('cash-transactions.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Record Cash Flow Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Transaction Type</label>
                        <select name="type" class="form-select shadow-sm" required>
                            <option value="inflow">Receivable / Money Coming IN (e.g., Savings, Grant)</option>
                            <option value="outflow">Payable / Money Going OUT (e.g., Rent, Debts)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <input type="text" name="description" class="form-control shadow-sm" placeholder="e.g., Office Rent, Client Savings..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Amount (<?php echo e($currency); ?>)</label>
                        <input type="number" name="amount" class="form-control shadow-sm font-monospace fw-bold" min="1" placeholder="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Transaction Date</label>
                        <input type="date" name="transaction_date" class="form-control shadow-sm" value="<?php echo e(date('Y-m-d')); ?>" required>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm px-4">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        body { background-color: #fff; }
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/transactions/cash-transactions/index.blade.php ENDPATH**/ ?>