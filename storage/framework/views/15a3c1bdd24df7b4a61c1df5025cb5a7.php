

<?php $__env->startSection('title', 'Loan Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">
                Loan #<?php echo e($loan->reference_id ?? str_pad($loan->id, 4, '0', STR_PAD_LEFT)); ?>

                <?php if($loan->status == 'paid'): ?>
                    <span class="badge bg-success" style="font-size: 0.5em; vertical-align: middle;">PAID</span>
                <?php elseif($loan->status == 'defaulted'): ?>
                    <span class="badge bg-danger" style="font-size: 0.5em; vertical-align: middle;">DEFAULTED</span>
                <?php else: ?>
                    <span class="badge bg-primary" style="font-size: 0.5em; vertical-align: middle;">ACTIVE</span>
                <?php endif; ?>
            </h1>
            <p class="mb-0 text-muted">
                Client: <strong><?php echo e($loan->client->name); ?></strong> | Phone: <?php echo e($loan->client->phone_number); ?>

            </p>
        </div>
        <div>
            <a href="<?php echo e(route('loans.index')); ?>" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            
            <a href="<?php echo e(route('loans.downloadAgreement', $loan->id)); ?>" class="btn btn-dark shadow-sm ms-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Agreement
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle me-2"></i> <?php echo e(session('status')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    
    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <strong><i class="fas fa-exclamation-circle me-2"></i> Payment Failed to Save!</strong>
            <ul class="mb-0 mt-2">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        
        
        <div class="col-lg-4 mb-4">
            
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Client Profile</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-secondary"></i>
                    </div>
                    <h5 class="font-weight-bold"><?php echo e($loan->client->name); ?></h5>
                    <p class="text-muted mb-2"><?php echo e($loan->client->business_occupation ?? 'Occupation N/A'); ?></p>
                    <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt me-1"></i> <?php echo e($loan->client->address); ?></p>
                    <hr>
                    <a href="<?php echo e(route('clients.edit', $loan->client->id)); ?>" class="btn btn-sm btn-outline-primary w-100">View Full Profile</a>
                </div>
            </div>

            
            <div class="card shadow-sm mb-4 border-start border-primary border-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Financial Summary</h6>
                </div>
                <div class="card-body">
                    <?php
                        $manager = Auth::user()->loanManager;
                        $currency = $manager->currency_symbol ?? 'UGX';
                        
                        $principal = $loan->principal_amount;
                        // Calculate interest based on rate
                        $calculatedInterest = $principal * ($loan->interest_rate / 100);
                        // Use stored interest_amount if available, otherwise calculated
                        $interest = $loan->interest_amount ?? $calculatedInterest;
                        
                        $totalDue = $principal + $interest;
                        $totalPaid = $loan->payments->sum('amount_paid');
                        $balance = max(0, $totalDue - $totalPaid);
                        
                        $progress = ($totalDue > 0) ? ($totalPaid / $totalDue) * 100 : 0;
                    ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-muted fw-bold">Principal</span>
                        <span class="fw-bold"><?php echo e(number_format($principal)); ?> <?php echo e($currency); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-muted fw-bold">Interest (<?php echo e($loan->interest_rate); ?>%)</span>
                        <span class="fw-bold"><?php echo e(number_format($interest)); ?> <?php echo e($currency); ?></span>
                    </div>
                    
                    <hr class="my-2">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-dark fw-bold">Total Due</span>
                        <span class="fw-bold text-dark"><?php echo e(number_format($totalDue)); ?> <?php echo e($currency); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-success fw-bold">Paid</span>
                        <span class="fw-bold text-success"><?php echo e(number_format($totalPaid)); ?> <?php echo e($currency); ?></span>
                    </div>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo e($progress); ?>%"></div>
                    </div>

                    <div class="p-3 bg-light rounded border border-danger">
                        <div class="small text-danger text-uppercase fw-bold mb-1">Balance Due</div>
                        <div class="h4 mb-0 font-weight-bold text-danger"><?php echo e(number_format($balance)); ?> <?php echo e($currency); ?></div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header border-bottom-0">
                    
                    <ul class="nav nav-tabs card-header-tabs" id="loanTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab">
                                <i class="fas fa-calendar-alt me-2"></i> Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                                <i class="fas fa-history me-2"></i> History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="guarantors-tab" data-bs-toggle="tab" data-bs-target="#guarantors" type="button" role="tab">
                                <i class="fas fa-users me-2"></i> Guarantors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="collateral-tab" data-bs-toggle="tab" data-bs-target="#collateral" type="button" role="tab">
                                <i class="fas fa-car me-2"></i> Collateral
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="loanTabsContent">
                        
                        
                        <div class="tab-pane fade show active" id="schedule" role="tabpanel">
                            <h6 class="font-weight-bold text-primary mb-3">Repayment Schedule</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Due Date</th>
                                            <th class="text-end">Installment</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(isset($schedule) && count($schedule) > 0): ?>
                                            <?php $__currentLoopData = $schedule; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e($row['period']); ?></td>
                                                <td><?php echo e(\Carbon\Carbon::parse($row['due_date'])->format('d M, Y')); ?></td>
                                                <td class="text-end fw-bold"><?php echo e(number_format($row['payment_amount'])); ?></td>
                                                <td class="text-end"><?php echo e(number_format(max(0, $row['balance']))); ?></td>
                                            </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    Schedule calculation data unavailable.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        
                        <div class="tab-pane fade" id="payments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-success m-0">Payment History</h6>
                                
                                <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                    <i class="fas fa-plus me-1"></i> Record Payment
                                </button>
                            </div>
                            
                            <?php if($loan->payments->count() > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt #</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $loan->payments->sortByDesc('payment_date'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e(\Carbon\Carbon::parse($payment->payment_date)->format('d M, Y')); ?></td>
                                                <td class="text-muted small"><?php echo e($payment->reference_id ?? $payment->receipt_number ?? '-'); ?></td>
                                                <td class="text-success fw-bold"><?php echo e(number_format($payment->amount_paid)); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo e(ucfirst($payment->payment_method)); ?></span></td>
                                                <td>
                                                    <a href="<?php echo e(route('payments.receipt', $payment->id)); ?>" class="btn btn-sm btn-info text-white" target="_blank" title="Print Receipt">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    
                                                    <a href="<?php echo e(route('payments.edit', $payment->id)); ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 bg-light rounded">
                                    <i class="fas fa-receipt fa-3x text-secondary mb-3"></i>
                                    <p class="text-muted">No payments recorded yet.</p>
                                    <button class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">Record First Payment</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        
                        <div class="tab-pane fade" id="guarantors" role="tabpanel">
                            <?php if($loan->guarantor_name || ($loan->guarantors && $loan->guarantors->count() > 0)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Phone</th>
                                                <th>Relationship</th>
                                                <th>Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if($loan->guarantors && $loan->guarantors->count() > 0): ?>
                                                <?php $__currentLoopData = $loan->guarantors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo e($g->first_name); ?> <?php echo e($g->last_name); ?></td>
                                                    <td><?php echo e($g->phone_number); ?></td>
                                                    <td><?php echo e($g->relationship_to_borrower); ?></td>
                                                    <td><?php echo e($g->address); ?></td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php elseif($loan->guarantor_name): ?>
                                                
                                                <tr>
                                                    <td class="fw-bold"><?php echo e($loan->guarantor_name); ?></td>
                                                    <td><?php echo e($loan->guarantor_phone); ?></td>
                                                    <td><?php echo e($loan->guarantor_relationship); ?></td>
                                                    <td><?php echo e($loan->guarantor_address); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">No guarantor details recorded.</div>
                            <?php endif; ?>
                        </div>

                        
                        <div class="tab-pane fade" id="collateral" role="tabpanel">
                            <?php if($loan->collateral_name || ($loan->collaterals && $loan->collaterals->count() > 0)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Value</th>
                                                <th>Description/Condition</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if($loan->collaterals && $loan->collaterals->count() > 0): ?>
                                                <?php $__currentLoopData = $loan->collaterals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo e($c->name); ?> <?php echo e($c->collateral_type); ?></td>
                                                    <td><?php echo e(number_format($c->valuation_amount ?? $c->value)); ?></td>
                                                    <td><?php echo e($c->description); ?> (<?php echo e($c->condition); ?>)</td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php elseif($loan->collateral_name): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo e($loan->collateral_name); ?></td>
                                                    <td><?php echo e(number_format((float)$loan->collateral_value)); ?></td>
                                                    <td><?php echo e($loan->collateral_description); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">No collateral recorded.</div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?php echo e(route('payments.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="loan_id" value="<?php echo e($loan->id); ?>">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd me-2"></i>Record New Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body bg-light">
                    
                    <div class="card p-3 border-success bg-white shadow-sm mb-3">
                        <h6 class="text-success fw-bold mb-3 border-bottom pb-2">Payment Breakdown</h6>
                        
                        <div class="row mb-2">
                            <div class="col-7"><label class="mb-0 fw-bold">Principal Paid:</label></div>
                            <div class="col-5">
                                <input type="number" name="principal_paid" id="detailModalPrincipal" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calcDetailModalTotal()">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-7"><label class="mb-0 fw-bold">Interest Paid:</label></div>
                            <div class="col-5">
                                <input type="number" name="interest_paid" id="detailModalInterest" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calcDetailModalTotal()">
                            </div>
                        </div>

                        <div class="row mt-3 pt-2 border-top">
                            <div class="col-7"><label class="mb-0 fw-bold text-uppercase">Total Amount:</label></div>
                            <div class="col-5 text-end"><h5 class="mb-0 fw-bold text-success" id="detailModalTotal">0.00</h5></div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control shadow-sm" value="<?php echo e(date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Method</label>
                            <select name="payment_method" class="form-select shadow-sm">
                                <option value="Cash" selected>Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Money">Mobile Money</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Reference / Notes</label>
                        <input type="text" name="notes" class="form-control shadow-sm" placeholder="Optional details">
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Live calculation logic for the Split Payment Modal on the Loan Details page
    function calcDetailModalTotal() {
        let p = parseFloat(document.getElementById('detailModalPrincipal').value) || 0;
        let i = parseFloat(document.getElementById('detailModalInterest').value) || 0;
        let display = document.getElementById('detailModalTotal');
        if(display) {
            display.innerText = (p + i).toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    }
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/loans/show.blade.php ENDPATH**/ ?>