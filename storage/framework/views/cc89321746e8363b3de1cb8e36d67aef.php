<?php
$currency = \App\Models\LoanManager::getCurrency();
?>


<?php $__env->startSection('title', 'Repayment Calculator'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4 text-gray-800">Repayment Schedule Calculator</h1>

    
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Enter Loan Details</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('loans.showCalculator')); ?>">
                <input type="hidden" name="calculate" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="principal_amount" class="form-label">Principal Amount (<?php echo e($currency); ?>)</label>
                        <input type="number" step="100" name="principal_amount" id="principal_amount" class="form-control" value="<?php echo e($principal); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                        <input type="number" step="0.1" name="interest_rate" id="interest_rate" class="form-control" value="<?php echo e($interestRate); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="term" class="form-label">Loan Term (Periods)</label>
                        <input type="number" name="term" id="term" class="form-control" value="<?php echo e($term); ?>" required min="1">
                    </div>
                    <div class="col-md-3">
                        <label for="repayment_frequency" class="form-label">Frequency</label>
                        <select name="repayment_frequency" id="repayment_frequency" class="form-control" required>
                            <option value="Daily" <?php echo e($frequency == 'Daily' ? 'selected' : ''); ?>>Daily</option>
                            <option value="Weekly" <?php echo e($frequency == 'Weekly' ? 'selected' : ''); ?>>Weekly</option>
                            <option value="Monthly" <?php echo e($frequency == 'Monthly' ? 'selected' : ''); ?>>Monthly</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3"><i class="fas fa-calculator me-2"></i> Calculate Schedule</button>
            </form>
        </div>
    </div>

    
    <?php if($calculationPerformed): ?>

    <div class="row text-center mb-3">
        <div class="col-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Principal</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($currency); ?> <?php echo e(number_format($principal, 0)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Interest</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($currency); ?> <?php echo e(number_format($totalInterest, 0)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Repayable</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($currency); ?> <?php echo e(number_format($totalRepayable, 0)); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow mb-4 mt-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Projected Schedule</h6>
        </div>
        <div class="card-body">
            <?php if(count($schedule) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Due Date</th>
                                <th class="text-end">Payment Amount (<?php echo e($currency); ?>)</th>
                                <th class="text-end">Principal (<?php echo e($currency); ?>)</th>
                                <th class="text-end">Interest (<?php echo e($currency); ?>)</th>
                                <th class="text-end">Remaining Balance (<?php echo e($currency); ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $schedule; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($item['period']); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($item['due_date'])->format('d M, Y')); ?></td>
                                <td class="text-end"><?php echo e(number_format($item['payment_amount'], 0)); ?></td>
                                <td class="text-end"><?php echo e(number_format($item['principal'], 0)); ?></td>
                                <td class="text-end"><?php echo e(number_format($item['interest'], 0)); ?></td>
                                <td class="text-end"><?php echo e(number_format($item['balance'], 0)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No schedule generated. Please check your principal and term values.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/loans/calculator.blade.php ENDPATH**/ ?>