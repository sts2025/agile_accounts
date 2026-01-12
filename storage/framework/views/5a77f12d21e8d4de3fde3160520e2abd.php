<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>

<?php $__env->startSection('title', 'My Expenses'); ?>

<?php $__env->startSection('content'); ?>
<div class="card" id="printable-area">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h1>My Expenses</h1>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('expenses.index')); ?>" class="mb-4 p-3 bg-light border rounded no-print">
            <div class="row align-items-end">
                <div class="col-md-4"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="<?php echo e($startDate); ?>"></div>
                <div class="col-md-4"><label>End Date</label><input type="date" name="end_date" class="form-control" value="<?php echo e($endDate); ?>"></div>
                <div class="col-md-4"><button type="submit" class="btn btn-secondary w-100">Filter Report</button></div>
            </div>
        </form>

        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 25%;">Category</th>
                    <th style="width: 40%;">Description</th> 
                    <th class="text-end" style="width: 20%;">Amount (<?php echo e($currency); ?>)</th> 
                </tr>
            </thead>
            <tbody>
                <?php
                    $totalExpenses = 0;
                ?>
                <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $totalExpenses += $expense->amount;
                    ?>
                    <tr>
                        <td><?php echo e(\Carbon\Carbon::parse($expense->expense_date)->format('d M, Y')); ?></td>
                        <td><?php echo e($expense->category->name ?? 'Uncategorized'); ?></td>
                        <td><?php echo e($expense->description ?? 'N/A'); ?></td> 
                        <td class="text-end text-danger"><?php echo e(number_format($expense->amount, 0)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="text-center">No expenses found for the selected period.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-group-divider fw-bold">
                <tr class="table-danger">
                    <td colspan="3" class="text-end">TOTAL EXPENSES:</td> 
                    <td class="text-end"><?php echo e($currency); ?> <?php echo e(number_format($totalExpenses, 0)); ?></td> 
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
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/transactions/expenses/index.blade.php ENDPATH**/ ?>