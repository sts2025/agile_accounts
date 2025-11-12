<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
// NOTE: Assuming $bankTransactions is passed to the view, not $transactions
$bankTransactions = $bankTransactions ?? $transactions ?? collect(); 
$startDate = $startDate ?? \Carbon\Carbon::now()->startOfMonth()->toDateString();
$endDate = $endDate ?? \Carbon\Carbon::now()->endOfMonth()->toDateString();
?>


<?php $__env->startSection('title', 'Bank Deposits & Withdrawals'); ?>

<?php $__env->startSection('content'); ?>
<div class="card" id="printable-area">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h1>Bank Deposits & Withdrawals</h1>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('bank-transactions.index')); ?>" class="mb-4 p-3 bg-light border rounded no-print">
            <div class="row align-items-end">
                <div class="col-md-4"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="<?php echo e($startDate); ?>"></div>
                <div class="col-md-4"><label>End Date</label><input type="date" name="end_date" class="form-control" value="<?php echo e($endDate); ?>"></div>
                <div class="col-md-4"><button type="submit" class="btn btn-secondary w-100">Filter Report</button></div>
            </div>
        </form>

        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-end">Amount (<?php echo e($currency); ?>)</th> 
                </tr>
            </thead>
            <tbody>
                <?php
                    $totalDeposits = 0;
                    $totalWithdrawals = 0;
                ?>
                <?php $__empty_1 = true; $__currentLoopData = $bankTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        if ($transaction->type === 'Deposit') {
                            $totalDeposits += $transaction->amount;
                        } else {
                            $totalWithdrawals += $transaction->amount;
                        }
                    ?>
                    <tr>
                        <td><?php echo e(\Carbon\Carbon::parse($transaction->deposit_date)->format('d M, Y')); ?></td>
                        <td><span class="badge bg-<?php echo e($transaction->type === 'Deposit' ? 'success' : 'danger'); ?>"><?php echo e(ucfirst($transaction->type)); ?></span></td>
                        <td><?php echo e($transaction->description ?? 'N/A'); ?></td>
                        <td class="text-end <?php echo e($transaction->type === 'Deposit' ? 'text-success' : 'text-danger'); ?>"><?php echo e(number_format($transaction->amount, 0)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="text-center">No bank transactions found for the selected period.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-group-divider fw-bold">
                <tr>
                    <td colspan="3" class="text-end">Total Deposits (Cash to Bank)</td>
                    <td class="text-end text-success"><?php echo e($currency); ?> <?php echo e(number_format($totalDeposits, 0)); ?></td> 
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Total Withdrawals (Bank to Cash)</td>
                    <td class="text-end text-danger"><?php echo e($currency); ?> <?php echo e(number_format($totalWithdrawals, 0)); ?></td> 
                </tr>
                <tr class="table-info">
                    <td colspan="3" class="text-end">Net Bank Movement</td>
                    <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalDeposits - $totalWithdrawals, 0)); ?></td> 
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none; }
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/transactions/bank-transactions/index.blade.php ENDPATH**/ ?>