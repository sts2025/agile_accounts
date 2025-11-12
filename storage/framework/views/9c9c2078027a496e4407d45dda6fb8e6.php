<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>

<?php $__env->startSection('title', 'Master Transaction List (General Ledger)'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mt-4" id="printable-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Master Transaction List</h1>
            <h5>(General Ledger)</h5>
            <p>A complete list of every single transaction across all modules, sorted by date.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>

    
    <form method="GET" action="<?php echo e(route('reports.general-ledger')); ?>" class="mb-4 p-3 bg-light border rounded no-print">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo e($startDate); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo e($endDate); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary w-100">Filter Report</button>
            </div>
        </div>
    </form>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Description / Source</th>
                        <th class="text-end">Cash In (<?php echo e($currency); ?>)</th> 
                        <th class="text-end">Cash Out (<?php echo e($currency); ?>)</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $totalIn = 0;
                        $totalOut = 0;
                        $runningBalance = 0; // Added running balance for context
                    ?>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $totalIn += $tx->amount_in;
                            $totalOut += $tx->amount_out;
                            $runningBalance += $tx->amount_in - $tx->amount_out; // Calculate running balance
                        ?>
                        <tr>
                            <td><?php echo e(\Carbon\Carbon::parse($tx->date)->format('d M, Y')); ?></td>
                            <td><?php echo e($tx->description); ?></td>
                            <td class="text-end text-success">
                                <?php echo e($tx->amount_in > 0 ? number_format($tx->amount_in, 0) : '-'); ?>

                            </td>
                            <td class="text-end text-danger">
                                <?php echo e($tx->amount_out > 0 ? number_format($tx->amount_out, 0) : '-'); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No transactions found for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">Total Movement</td>
                        <td class="text-end text-success"><?php echo e($currency); ?> <?php echo e(number_format($totalIn, 0)); ?></td> 
                        <td class="text-end text-danger"><?php echo e($currency); ?> <?php echo e(number_format($totalOut, 0)); ?></td> 
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end h5">Net Cash Movement (In - Out)</td>
                        <td colspan="2" class="text-end h5 <?php echo e(($totalIn - $totalOut) >= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($currency); ?> <?php echo e(number_format($totalIn - $totalOut, 0)); ?> 
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
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
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/general-ledger.blade.php ENDPATH**/ ?>