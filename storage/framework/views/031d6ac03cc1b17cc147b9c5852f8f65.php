<?php
// Note: Assumes variables like $totalClients, $chartData, $reportDate, etc., 
// are correctly passed by the DashboardController.
$currency = \App\Models\LoanManager::getCurrency();
?>

<?php $__env->startSection('title', 'Loan Manager Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        .icon-lg { font-size: 2.5rem; opacity: 0.8; }
        .quick-actions .btn {
            margin-bottom: 10px;
            margin-right: 5px;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <div class="btn-group" role="group">
            <a href="<?php echo e(route('clients.create')); ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Add New Client
            </a>
            <a href="<?php echo e(route('loans.create')); ?>" class="btn btn-success">
                <i class="fas fa-hand-holding-usd me-1"></i> Create New Loan
            </a>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="fas fa-dollar-sign me-1"></i> Record Payment
            </button>
        </div>
    </div>

    <div class="row">
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Clients</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($totalClients ?? 0); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300 icon-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Loans</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($activeLoansCount ?? 0); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300 icon-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Loaned Amount</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($currency); ?> <?php echo e(number_format($totalLoanedAmount ?? 0, 0)); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-piggy-bank fa-2x text-gray-300 icon-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
        </div>
        <div class="card-body quick-actions">
            
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPayableReceivableModal">
                Add Payable / Receivable
            </button>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addBankingModal">
                Add Banking
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                Add Expenses
            </button>
            
            <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-success">View Client List</a>
            <a href="<?php echo e(route('loans.index')); ?>" class="btn btn-secondary">View Loan List</a>
            <a href="<?php echo e(route('reports.print-forms')); ?>" class="btn btn-dark">Print Forms</a>
            <a href="<?php echo e(route('reports.general-ledger')); ?>" class="btn btn-warning">
                View General Ledger
            </a>

        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Loans vs. Payments (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="loanVsPaymentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Report (<?php echo e($reportDate->format('d M, Y')); ?>)</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('reports.daily')); ?>" class="d-flex mb-3">
                        <input type="date" name="date" class="form-control me-2" value="<?php echo e($reportDate->format('Y-m-d')); ?>">
                        <button type="submit" class="btn btn-secondary">Go</button>
                    </form>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Opening Balance:</span>
                        <strong><?php echo e($currency); ?> <?php echo e(number_format($openingBalance, 0)); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Payments Received:</span>
                        <strong>+ <?php echo e($currency); ?> <?php echo e(number_format($totalPaidCash, 0)); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Loans Given:</span>
                        <strong>- <?php echo e($currency); ?> <?php echo e(number_format($totalLoanGiven, 0)); ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold h5">
                        <span>Closing Stock:</span>
                        <strong><?php echo e($currency); ?> <?php echo e(number_format($closingStock, 0)); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    
    
    
    <?php $__env->startPush('modals'); ?>
    
        
        <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recordPaymentModalLabel">Record a Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('payments.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="client_id_select" class="form-label">Client</label>
                                <select class="form-select" id="client_id_select" name="client_id" required>
                                    <option value="" selected disabled>Select a client</option>
                                    <?php $__currentLoopData = $allClientsWithLoans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($client->id); ?>"><?php echo e($client->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="loan_id_select" class="form-label">Loan</label>
                                <select class="form-select" id="loan_id_select" name="loan_id" required disabled>
                                    <option value="" selected disabled>Select a client first</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="amount_paid" class="form-label">Amount Paid (<?php echo e($currency); ?>)</label>
                                <input type="number" class="form-control" id="amount_paid" name="amount_paid" required>
                            </div>
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="payment_notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="addBankingModal" tabindex="-1" aria-labelledby="addBankingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBankingModalLabel">Add Bank Deposit / Withdrawal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('bank-transactions.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="bank_description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="bank_description" name="description" placeholder="e.g., Cash Deposit to Bank" required>
                            </div>
                            <div class="mb-3">
                                <label for="bank_type" class="form-label">Transaction Type</label>
                                <select class="form-select" id="bank_type" name="type">
                                    <option value="Deposit">Deposit (Cash to Bank)</option>
                                    <option value="Withdrawal">Withdrawal (Bank to Cash)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="bank_amount" class="form-label">Amount (<?php echo e($currency); ?>)</label>
                                <input type="number" class="form-control" id="bank_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="bank_date" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="bank_date" name="transaction_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addExpenseModalLabel">Add Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('expenses.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="expense_category_id" class="form-label">Expense Category</label>
                                <select class="form-select" id="expense_category_id" name="expense_category_id" required>
                                    <option value="" disabled selected>Select a category</option>
                                    <?php $__currentLoopData = $expenseCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($category->id); ?>"><?php echo e($category->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="expense_amount" class="form-label">Amount (<?php echo e($currency); ?>)</label>
                                <input type="number" class="form-control" id="expense_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="expense_date" class="form-label">Expense Date</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="addPayableReceivableModal" tabindex="-1" aria-labelledby="addPayableReceivableModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPayableReceivableModalLabel">Add Payable / Receivable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('cash-transactions.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="pr_description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="pr_description" name="description" placeholder="e.g., Rent for November" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pr_type" class="form-label">Type</label>
                                <select class="form-select" id="pr_type" name="type" required>
                                    <option value="payable">Payable (Cash Out)</option>
                                    <option value="receivable">Receivable (Cash In)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="pr_amount" class="form-label">Amount (<?php echo e($currency); ?>)</label>
                                <input type="number" class="form-control" id="pr_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="pr_date" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="pr_date" name="transaction_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php $__env->stopPush(); ?>

    <?php $__env->startPush('scripts'); ?>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // --- Chart Logic ---
                // Ensure $chartData is available here for chart rendering

                // --- Payment Dropdown Logic ---
                const allClientsData = <?php echo json_encode($allClientsWithLoans, 15, 512) ?>;
                const clientLoansMap = {};
                allClientsData.forEach(client => {
                    clientLoansMap[client.id] = client.loans;
                });

                const clientSelect = document.getElementById('client_id_select');
                const loanSelect = document.getElementById('loan_id_select');
                const loanCurrencySymbol = '<?php echo e($currency); ?>';

                clientSelect.addEventListener('change', function() {
                    const selectedClientId = this.value;
                    loanSelect.innerHTML = '<option value="" selected disabled>Select a loan</option>';
                    loanSelect.disabled = true;

                    if (selectedClientId && clientLoansMap[selectedClientId]) {
                        const loans = clientLoansMap[selectedClientId];
                        loans.forEach(loan => {
                            const option = document.createElement('option');
                            option.value = loan.id;
                            option.text = `Loan #${loan.id} - ${loanCurrencySymbol} ${parseInt(loan.principal_amount).toLocaleString()}`;
                            loanSelect.appendChild(option);
                        });
                        loanSelect.disabled = false;
                    }
                });
            });
        </script>
    <?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/dashboard.blade.php ENDPATH**/ ?>