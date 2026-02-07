<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Agile Accounts'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { display: flex; background-color: #f4f7f6; }
        .sidebar { width: 280px; min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; top: 0; left: 0; overflow-y: auto; z-index: 1000; }
        .sidebar .logo { text-align: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .sidebar .nav-link { color: #e1e8ec; padding: 10px 15px; border-radius: 5px; margin-bottom: 5px; transition: all 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #34495e; color: white; }
        
        .sidebar .nav-link[data-bs-toggle="collapse"]::after { content: ' ▸'; float: right; transition: transform 0.2s; }
        .sidebar .nav-link[data-bs-toggle="collapse"][aria-expanded="true"]::after { content: ' ▾'; transform: rotate(0deg); }
        
        .sidebar .collapse .nav-link { font-size: 0.9em; padding-left: 20px; background-color: rgba(0,0,0,0.1); }
        
        .main-content { margin-left: 280px; padding: 30px; width: calc(100% - 280px); display: flex; flex-direction: column; min-height: 100vh; position: relative; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #dee2e6; }
        .top-menu { display: flex; align-items: center; gap: 15px; }
        
        .app-footer { padding-top: 20px; text-align: center; border-top: 1px solid #ddd; margin-top: auto; font-size: 0.85rem; color: #6c757d; padding-bottom: 10px; }
        .sidebar-sub-header { font-size: 0.7rem; text-transform: uppercase; color: #8fa0b1; margin: 15px 0 5px 15px; font-weight: bold; letter-spacing: 1px; }
        .submenu-header { font-size: 0.75rem; text-transform: uppercase; color: #8fa0b1; margin: 10px 0 5px 20px; font-weight: bold; }

        /* BACKDROP KILLER - STRONG CSS OVERRIDE */
        .modal-backdrop { 
            display: none !important; 
            visibility: hidden !important; 
            opacity: 0 !important; 
            pointer-events: none !important; 
            z-index: -1 !important;
        }
        body.modal-open { 
            overflow: auto !important; 
            padding-right: 0 !important; 
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <div class="sidebar shadow">
        <div class="logo">
            <a href="<?php echo e(route('dashboard')); ?>" class="text-decoration-none">
                
                <h4 class="text-white mb-0 fw-bold">
                    <?php echo e(optional(Auth::user()->getCompany())->company_name ?? 'Agile Accounts'); ?>

                </h4>
                <small class="text-info" style="font-size: 0.7rem; letter-spacing: 1px;">MANAGEMENT PORTAL</small>
            </a>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item"> 
                <a class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>"> 
                    <i class="bi bi-grid-fill me-2"></i> Dashboard 
                </a> 
            </li>

            <li class="nav-item"> 
                <a class="nav-link <?php echo e(request()->routeIs('payments.*') ? 'active' : ''); ?>" href="<?php echo e(route('payments.index')); ?>" style="background-color: #3498db; color: white; margin-bottom: 15px;"> 
                    <i class="bi bi-cash-stack me-2"></i> All Payments 
                </a> 
            </li>

            <div class="sidebar-sub-header">Operations</div>

            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#clients-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-people-fill me-2"></i> Clients 
                </a>
                <div class="collapse <?php echo e(request()->routeIs('clients.*') ? 'show' : ''); ?>" id="clients-submenu">
                    <ul class="nav flex-column ms-2">
                        <div class="submenu-header">Actions</div>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('clients.index') && !request()->has('filter') ? 'active' : ''); ?>" href="<?php echo e(route('clients.index')); ?>">List Clients</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('clients.create') ? 'active' : ''); ?>" href="<?php echo e(route('clients.create')); ?>">Add New Client</a></li>
                        
                        <div class="submenu-header">Filters</div>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->input('filter') == 'not_paid' ? 'active' : ''); ?>" href="<?php echo e(route('clients.index', ['filter' => 'not_paid'])); ?>">Not Paid</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->input('filter') == 'with_loans' ? 'active' : ''); ?>" href="<?php echo e(route('clients.index', ['filter' => 'with_loans'])); ?>">With Loans</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->input('filter') == 'no_loans' ? 'active' : ''); ?>" href="<?php echo e(route('clients.index', ['filter' => 'no_loans'])); ?>">Without Loans</a></li>
                    </ul>
                </div>
            </li>
            
            
            <li class="nav-item"> 
                <a class="nav-link collapsed" href="#loans-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-journal-text me-2"></i> Loans 
                </a> 
                <div class="collapse <?php echo e(request()->routeIs('loans.*') ? 'show' : ''); ?>" id="loans-submenu">
                    <ul class="nav flex-column ms-2">
                        <div class="submenu-header">Actions</div>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('loans.index') && !request()->has('filter') ? 'active' : ''); ?>" href="<?php echo e(route('loans.index')); ?>">All Loans</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('loans.create') ? 'active' : ''); ?>" href="<?php echo e(route('loans.create')); ?>">Create Loan</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('loans.index', ['filter' => 'completed']) ? 'active' : ''); ?>" href="<?php echo e(route('loans.index', ['filter' => 'completed'])); ?>">Completed Loans</a></li>
                        
                        <div class="submenu-header">Tools</div>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('loans.showCalculator') ? 'active' : ''); ?>" href="<?php echo e(route('loans.showCalculator')); ?>">Loan Calculator</a></li>
                    </ul>
                </div>
            </li>
            
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#reports-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-bar-chart-fill me-2"></i> Reports 
                </a>
                <div class="collapse <?php echo e(request()->routeIs('reports.*') ? 'show' : ''); ?>" id="reports-submenu">
                    <ul class="nav flex-column ms-2">
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.daily') ? 'active' : ''); ?>" href="<?php echo e(route('reports.daily')); ?>">Daily Report</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.general-ledger') ? 'active' : ''); ?>" href="<?php echo e(route('reports.general-ledger')); ?>">General Ledger</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.trial-balance') ? 'active' : ''); ?>" href="<?php echo e(route('reports.trial-balance')); ?>">Trial Balance</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.profit-and-loss') ? 'active' : ''); ?>" href="<?php echo e(route('reports.profit-and-loss')); ?>">P&L Statement</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.balance-sheet') ? 'active' : ''); ?>" href="<?php echo e(route('reports.balance-sheet')); ?>">Balance Sheet</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.loan-aging') ? 'active' : ''); ?>" href="<?php echo e(route('reports.loan-aging')); ?>">Loan Aging Report</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('reports.print-forms') ? 'active' : ''); ?>" href="<?php echo e(route('reports.print-forms')); ?>">Print Forms</a></li>
                    </ul>
                </div>
            </li>
            
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#transactions-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-arrow-down-up me-2"></i> Transactions 
                </a>
                <div class="collapse <?php echo e(request()->routeIs(['bank-transactions.*', 'expenses.*', 'cash-transactions.*']) ? 'show' : ''); ?>" id="transactions-submenu">
                    <ul class="nav flex-column ms-2">
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('bank-transactions.index') ? 'active' : ''); ?>" href="<?php echo e(route('bank-transactions.index')); ?>">
                            <i class="fas fa-university me-2"></i> Bank Deposits
                        </a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('expenses.index') ? 'active' : ''); ?>" href="<?php echo e(route('expenses.index')); ?>">
                            <i class="fas fa-file-invoice me-2"></i> Expenses
                        </a></li>
                        <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('cash-transactions.index') ? 'active' : ''); ?>" href="<?php echo e(route('cash-transactions.index')); ?>">
                            <i class="fas fa-hand-holding-usd me-2"></i> Cash Flow
                        </a></li>
                    </ul>
                </div>
            </li>

            <div class="sidebar-sub-header">Administration</div>
            
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('manager.staff.*') ? 'active' : ''); ?>" href="<?php echo e(route('manager.staff.index')); ?>">
                    <i class="bi bi-person-badge-fill me-2"></i> Manage Staff
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('manager.settings.*') ? 'active' : ''); ?>" href="<?php echo e(route('manager.settings.edit')); ?>">
                    <i class="bi bi-gear-wide-connected me-2"></i> Business Settings
                </a>
            </li>

        </ul>
    </div>

    <div class="main-content">
        <div class="main-header bg-white px-3 rounded shadow-sm">
            
            <h5 class="mb-0 text-dark fw-bold text-uppercase">
                <?php echo e(optional(Auth::user()->getCompany())->company_name ?? 'Agile Accounts'); ?>

            </h5>
            
            <div class="top-menu">
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-md-block">
                        <div class="fw-bold small text-dark"><?php echo e(Auth::user()->name); ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?php echo e(Auth::user()->role == 'cashier' ? 'Cashier' : 'Loan Manager'); ?>

                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="<?php echo e(route('profile.edit')); ?>"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="dropdown-item text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-wrapper">
            <?php echo $__env->yieldContent('content'); ?>
        </div>

        <footer class="app-footer">
            Developed by <strong>BKR TECH </strong> &copy; <?php echo e(date('Y')); ?> | 
            Support: <a href="tel:<?php echo e(\App\Models\LoanManager::getGlobalSupportPhone()); ?>" class="fw-bold text-decoration-none">
                <?php echo e(\App\Models\LoanManager::getGlobalSupportPhone()); ?>

            </a>
        </footer>
    </div>

    <?php echo $__env->yieldPushContent('modals'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Function to remove stuck backdrops
            function removeBackdrop() {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0px';
            }

            // 1. Initial Cleanup
            removeBackdrop();

            // 2. MutationObserver: Watch for the backdrop element and destroy it instantly
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.classList.contains('modal-backdrop')) {
                            node.remove();
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = 'auto';
                        }
                    });
                });
            });

            // Start observing the body for added nodes
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/layouts/manager.blade.php ENDPATH**/ ?>