
<?php $__env->startSection('title', 'Client Ledger for ' . $client->name); ?>
<?php $__env->startSection('content'); ?>
<div class="card" id="printable-area">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Client Ledger: <?php echo e($client->name); ?></h1>
            <p class="mb-0">Phone: <?php echo e($client->phone_number); ?></p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Ledger</button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr><th>Date</th><th>Description</th><th class="text-end">Debit (Loan)</th><th class="text-end">Credit (Paid)</th><th class="text-end">Balance</th></tr>
            </thead>
            <tbody>
                <?php $balance = 0; ?>
                <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $balance += $transaction->debit - $transaction->credit; ?>
                    <tr>
                        <td><?php echo e(\Carbon\Carbon::parse($transaction->date)->format('Y-m-d')); ?></td>
                        <td><?php echo e($transaction->description); ?></td>
                        <td class="text-end"><?php echo e($transaction->debit > 0 ? number_format($transaction->debit, 0) : '-'); ?></td>
                        <td class="text-end"><?php echo e($transaction->credit > 0 ? number_format($transaction->credit, 0) : '-'); ?></td>
                        <td class="text-end fw-bold"><?php echo e(number_format($balance, 0)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="text-center">No transactions found for this client.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
                <tr><td colspan="4" class="text-end fw-bold fs-5">Final Balance</td><td class="text-end fw-bold fs-5"><?php echo e(number_format($balance, 0)); ?></td></tr>
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
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/clients/ledger.blade.php ENDPATH**/ ?>