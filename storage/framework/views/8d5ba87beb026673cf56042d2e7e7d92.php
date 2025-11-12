<?php
// Fetch currency once at the top
$currency = \App\Models\LoanManager::getCurrency();
?>


<?php $__env->startSection('title', 'Loan Aging Report'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">Loan Aging Analysis Statement</h1>
            <p class="text-muted mb-0">Analysis of overdue loans and arrears as of <?php echo e(\Carbon\Carbon::now()->format('d-M-Y')); ?>.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="fas fa-print me-2"></i> Print Report
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Client Name</th>
                        <th style="width: 15%;">Guarantor</th>
                        <th style="width: 10%;">Date Given</th>
                        <th style="width: 10%;">Next Due Date</th>
                        <th class="text-end" style="width: 12%;">Principal (<?php echo e($currency); ?>)</th>
                        <th class="text-end" style="width: 10%;">Interest (<?php echo e($currency); ?>)</th>
                        <th class="text-end" style="width: 10%;">Total Arrears (<?php echo e($currency); ?>)</th>
                        <th class="text-center" style="width: 8%;">Days Missed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        
                        <?php
                            $totalInterest = $loan->principal_amount * ($loan->interest_rate / 100);
                            $nextDueDate = $loan->repaymentSchedules->where('status', 'pending')->sortBy('due_date')->first()->due_date ?? 'N/A';
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo e(route('clients.show', $loan->client_id)); ?>"><?php echo e($loan->client->name ?? 'N/A'); ?></a>
                            </td>
                            <td>
                                <?php echo e($loan->guarantors->first()->first_name ?? 'N/A'); ?> <?php echo e($loan->guarantors->first()->last_name ?? ''); ?>

                            </td>
                            <td><?php echo e(\Carbon\Carbon::parse($loan->start_date)->format('d-M-Y')); ?></td>
                            <td><?php echo e($nextDueDate !== 'N/A' ? \Carbon\Carbon::parse($nextDueDate)->format('d-M-Y') : 'N/A'); ?></td>
                            <td class="text-end"><?php echo e(number_format($loan->principal_amount, 0)); ?></td>
                            <td class="text-end"><?php echo e(number_format($totalInterest, 0)); ?></td>
                            <td class="text-end text-danger fw-bold">
                                <?php echo e(number_format($loan->arrears, 0)); ?>

                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger"><?php echo e($loan->days_missed); ?> Days</span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle me-2"></i> No overdue loans found. All active loans are up to date.
                                <br>
                                **(If you expect to see data, please ensure you have created a payment schedule and missed at least one payment.)**
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if(count($loans) > 0): ?>
                <tfoot class="table-group-divider">
                    <tr>
                        <th colspan="6" class="text-end">TOTAL OUTSTANDING ARREARS:</th>
                        <th class="text-end text-danger"><?php echo e($currency); ?> <?php echo e(number_format($loans->sum('arrears'), 0)); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/reports/loan-aging.blade.php ENDPATH**/ ?>