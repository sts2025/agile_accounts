<?php
// Note: Assumes variables like $totalClients, $chartData, $reportDate, etc., 
// are correctly passed by the DashboardController.
$currencySymbol = $currency ?? \App\Models\LoanManager::getCurrency() ?? 'UGX';
?>

<?php $__env->startSection('title', 'Loan Manager Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Make Select2 match Bootstrap 5 styling */
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            color: #212529;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        
        .icon-lg { font-size: 2.5rem; opacity: 0.8; }
        .quick-actions .btn {
            margin-bottom: 10px;
            margin-right: 5px;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <strong><i class="fas fa-exclamation-circle me-2"></i> Action Failed!</strong>
            <ul class="mb-0 mt-2">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <div class="btn-group" role="group">
            <a href="<?php echo e(route('clients.create')); ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Add New Client
            </a>
            <a href="<?php echo e(route('loans.create')); ?>" class="btn btn-success">
                <i class="fas fa-hand-holding-usd me-1"></i> Create New Loan
            </a>
            <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($currencySymbol); ?> <?php echo e(number_format($totalLoanedAmount ?? 0, 0)); ?></div>
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
            <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#addBankingModal">
                Add Banking
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                Add Expenses
            </button>
            
            <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-success">View Client List</a>
            <a href="<?php echo e(route('loans.index')); ?>" class="btn btn-secondary">View Loan List</a>
            <a href="<?php echo e(route('reports.print-forms')); ?>" class="btn btn-dark">Print Forms</a>
            <a href="<?php echo e(route('reports.general-ledger')); ?>" class="btn btn-warning text-dark">
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
                        <strong><?php echo e($currencySymbol); ?> <?php echo e(number_format($openingBalance, 0)); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Payments Received:</span>
                        <strong>+ <?php echo e($currencySymbol); ?> <?php echo e(number_format($totalPaidCash, 0)); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Loans Given:</span>
                        <strong>- <?php echo e($currencySymbol); ?> <?php echo e(number_format($totalLoanGiven, 0)); ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold h5">
                        <span>Closing Stock:</span>
                        <strong><?php echo e($currencySymbol); ?> <?php echo e(number_format($closingStock, 0)); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php $__env->startPush('modals'); ?>
    
        
        <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="recordPaymentModalLabel">Record a Payment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('payments.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body bg-light">
                            <div class="mb-3">
                                <label for="client_id_select" class="form-label fw-bold small text-muted">Search & Select Client</label>
                                <select class="form-select shadow-sm" id="client_id_select" name="client_id" style="width: 100%;" required>
                                    <option value="" selected disabled>Type client name...</option>
                                    <?php $__currentLoopData = $allClientsWithLoans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($client->id); ?>" data-loans="<?php echo e(json_encode($client->loans)); ?>"><?php echo e($client->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="loan_id_select" class="form-label fw-bold small text-muted">Loan</label>
                                <select class="form-select shadow-sm" id="loan_id_select" name="loan_id" required disabled>
                                    <option value="" selected disabled>Select a client first</option>
                                </select>
                            </div>
                            
                            
                            <div class="card p-3 border-success bg-white shadow-sm mb-3">
                                <h6 class="text-success fw-bold mb-3 border-bottom pb-2">Payment Breakdown</h6>
                                <div class="row mb-2">
                                    <div class="col-7"><label class="mb-0 fw-bold">Principal Paid:</label></div>
                                    <div class="col-5">
                                        <input type="number" name="principal_paid" id="dashInputPrincipal" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calculateDashTotal()">
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-7"><label class="mb-0 fw-bold">Interest Paid:</label></div>
                                    <div class="col-5">
                                        <input type="number" name="interest_paid" id="dashInputInterest" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calculateDashTotal()">
                                    </div>
                                </div>
                                <div class="row mt-3 pt-2 border-top">
                                    <div class="col-7"><label class="mb-0 fw-bold text-uppercase">Total Amount:</label></div>
                                    <div class="col-5 text-end"><h5 class="mb-0 fw-bold text-success" id="dashDisplayTotal">0.00</h5></div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="payment_date" class="form-label fw-bold small text-muted">Date</label>
                                    <input type="date" class="form-control shadow-sm" id="payment_date" name="payment_date" value="<?php echo e(now()->toDateString()); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label for="payment_method" class="form-label fw-bold small text-muted">Method</label>
                                    <select class="form-select shadow-sm" id="payment_method" name="payment_method">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Mobile Money">Mobile Money</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reference_id" class="form-label small text-muted">Reference / Receipt # (Optional)</label>
                                <input type="text" class="form-control form-control-sm" id="reference_id" name="reference_id" placeholder="e.g. RCP-1234">
                            </div>
                            <div class="mb-3">
                                <label for="payment_notes" class="form-label small text-muted">Notes (Optional)</label>
                                <textarea class="form-control form-control-sm" id="payment_notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="addBankingModal" tabindex="-1" aria-labelledby="addBankingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-bold">Add Bank Deposit / Withdrawal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('bank-transactions.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body bg-light">
                            <div class="mb-3">
                                <label for="bank_description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="bank_description" name="description" required>
                            </div>
                            <div class="mb-3">
                                <label for="bank_type" class="form-label">Transaction Type</label>
                                <select class="form-select" id="bank_type" name="type">
                                    <option value="Deposit">Deposit (Cash to Bank)</option>
                                    <option value="Withdrawal">Withdrawal (Bank to Cash)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="bank_amount" class="form-label">Amount (<?php echo e($currencySymbol); ?>)</label>
                                <input type="number" class="form-control" id="bank_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="bank_date" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="bank_date" name="transaction_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-info text-white fw-bold">Save Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Add Expense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('expenses.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body bg-light">
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
                                <label for="expense_amount" class="form-label">Amount (<?php echo e($currencySymbol); ?>)</label>
                                <input type="number" class="form-control" id="expense_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="expense_date" class="form-label">Expense Date</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger fw-bold">Save Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="addPayableReceivableModal" tabindex="-1" aria-labelledby="addPayableReceivableModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">Add Payable / Receivable</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('cash-transactions.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body bg-light">
                            <div class="mb-3">
                                <label for="pr_description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="pr_description" name="description" required>
                            </div>
                            <div class="mb-3">
                                <label for="pr_type" class="form-label">Type</label>
                                <select class="form-select" id="pr_type" name="type" required>
                                    <option value="payable">Payable (Cash Out)</option>
                                    <option value="receivable">Receivable (Cash In)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="pr_amount" class="form-label">Amount (<?php echo e($currencySymbol); ?>)</label>
                                <input type="number" class="form-control" id="pr_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="pr_date" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="pr_date" name="transaction_date" value="<?php echo e(now()->toDateString()); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary fw-bold">Save Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="autoReceiptModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-md">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2"></i> Print Receipt</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 bg-light">
                        
                        <iframe id="receiptIframe" style="width: 100%; height: 500px; border: none;"></iframe>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-dark fw-bold px-4" onclick="printReceiptIframe()">
                            <i class="fas fa-print me-2"></i> Print Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php $__env->stopPush(); ?>

    <?php $__env->startPush('scripts'); ?>
        
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            // Live calculation logic for the Split Payment Modal on the Dashboard
            function calculateDashTotal() {
                let principal = parseFloat(document.getElementById('dashInputPrincipal').value) || 0;
                let interest = parseFloat(document.getElementById('dashInputInterest').value) || 0;
                let display = document.getElementById('dashDisplayTotal');
                if (display) {
                    display.innerText = (principal + interest).toLocaleString(undefined, {minimumFractionDigits: 2});
                }
            }

            $(document).ready(function() {
                // --- 1. Initialize Select2 on the Client Dropdown ---
                $('#client_id_select').select2({
                    dropdownParent: $('#recordPaymentModal'), // Required to work inside Bootstrap Modal
                    placeholder: "Type a client's name...",
                    allowClear: true,
                    width: '100%'
                });

                // --- 2. Dynamic Loan Selection based on Client Selection ---
                const loanCurrencySymbol = "<?php echo e($currencySymbol ?? ''); ?>";
                
                $('#client_id_select').on('change', function() {
                    let selectedOption = $(this).find(':selected');
                    let loansData = selectedOption.attr('data-loans'); 
                    let loanSelect = $('#loan_id_select');
                    
                    loanSelect.empty(); 
                    
                    if (loansData) {
                        let loans = JSON.parse(loansData);
                        
                        if(loans.length > 0) {
                            loanSelect.append(new Option('Select a loan', '', true, true));
                            loanSelect.find('option:first').prop('disabled', true);

                            loans.forEach(function(loan) {
                                let principal = parseFloat(loan.principal_amount) || 0;
                                let interestRate = parseFloat(loan.interest_rate) || 0;
                                let totalDue = principal + (principal * (interestRate / 100));
                                
                                loanSelect.append(new Option('Loan #' + loan.id + ' (Total Expected: ' + loanCurrencySymbol + ' ' + totalDue.toLocaleString() + ')', loan.id));
                            });
                            loanSelect.prop('disabled', false);
                        } else {
                            loanSelect.append(new Option('No active loans found', ''));
                            loanSelect.prop('disabled', true);
                        }
                    } else {
                        loanSelect.append(new Option('Select a client first', ''));
                        loanSelect.prop('disabled', true);
                    }
                });

                // --- 3. Chart Logic ---
                const ctx = document.getElementById('loanVsPaymentChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($chartData['labels'] ?? [], 15, 512) ?>,
                            datasets: [
                                {
                                    label: 'Loans Given',
                                    data: <?php echo json_encode($chartData['loans'] ?? [], 15, 512) ?>,
                                    borderColor: 'rgb(231, 74, 59)',
                                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Payments Received',
                                    data: <?php echo json_encode($chartData['payments'] ?? [], 15, 512) ?>,
                                    borderColor: 'rgb(28, 200, 138)',
                                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' }
                            },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                }

                // --- 4. BULLETPROOF AUTO-POPUP RECEIPT LOGIC ---
                <?php if(session('print_receipt')): ?>
                    let receiptUrl = "<?php echo e(route('payments.receipt', session('print_receipt'))); ?>";
                    
                    // Put the receipt inside the iframe
                    document.getElementById('receiptIframe').src = receiptUrl;
                    
                    // Trigger the Bootstrap Modal to slide down
                    let receiptModal = new bootstrap.Modal(document.getElementById('autoReceiptModal'));
                    receiptModal.show();
                <?php endif; ?>
            });

            // --- 5. Function to trigger the printer from the iframe ---
            function printReceiptIframe() {
                let iframe = document.getElementById('receiptIframe');
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/dashboard.blade.php ENDPATH**/ ?>