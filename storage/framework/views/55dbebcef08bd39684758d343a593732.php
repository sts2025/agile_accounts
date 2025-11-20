<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $__env->yieldContent('title', 'Loan Manager'); ?></title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <link href="<?php echo e(asset('css/sb-admin-2.min.css')); ?>" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body id="page-top">

    <div id="wrapper">

        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo e(route('dashboard')); ?>">
                <div class="sidebar-brand-icon">
                    <img src="<?php echo e(asset('img/logo.png')); ?>" alt="Agile Accounts Logo" style="height: 40px;">
                </div>
                <div class="sidebar-brand-text mx-3">Agile Accounts</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <a class="nav-link" href="<?php echo e(route('dashboard')); ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Core
            </div>

            <li class="nav-item <?php echo e(request()->routeIs('clients.*') ? 'active' : ''); ?>">
                <a class="nav-link" href="<?php echo e(route('clients.index')); ?>">
                    <i class="fas fa-fw fa-users"></i>
                    <span>My Clients</span>
                </a>
            </li>

            <li class="nav-item <?php echo e(request()->routeIs('loans.index') ? 'active' : ''); ?>">
                <a class="nav-link" href="<?php echo e(route('loans.index')); ?>">
                    <i class="fas fa-fw fa-hand-holding-usd"></i>
                    <span>My Loans</span>
                </a>
            </li>

            <li class="nav-item <?php echo e(request()->routeIs('loans.showCalculator') ? 'active' : ''); ?>">
                <a class="nav-link" href="<?php echo e(route('loans.showCalculator')); ?>">
                    <i class="fas fa-fw fa-calculator"></i>
                    <span>Loan Calculator</span>
                </a>
            </li>

            <li class="nav-item <?php echo e(request()->is('reports*') ? 'active' : ''); ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports"
                    aria-expanded="true" aria-controls="collapseReports">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Reports</span>
                </a>
                <div id="collapseReports" class="collapse <?php echo e(request()->is('reports*') ? 'show' : ''); ?>" aria-labelledby="headingReports" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo e(request()->routeIs('reports.daily') ? 'active' : ''); ?>" href="<?php echo e(route('reports.daily')); ?>">Daily Report</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('reports.general-ledger') ? 'active' : ''); ?>" href="<?php echo e(route('reports.general-ledger')); ?>">General Ledger</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('reports.trial-balance') ? 'active' : ''); ?>" href="<?php echo e(route('reports.trial-balance')); ?>">Trial Balance</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('reports.profit-and-loss') ? 'active' : ''); ?>" href="<?php echo e(route('reports.profit-and-loss')); ?>">P&L Statement</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('reports.balance-sheet') ? 'active' : ''); ?>" href="<?php echo e(route('reports.balance-sheet')); ?>">Balance Sheet</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('reports.loan-aging') ? 'active' : ''); ?>" href="<?php echo e(route('reports.loan-aging')); ?>">Loan Aging Report</a>
                    </div>
                </div>
            </li>

            <li class="nav-item <?php echo e(request()->is('*-transactions*') || request()->is('expenses*') ? 'active' : ''); ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTransactions"
                    aria-expanded="true" aria-controls="collapseTransactions">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
                <div id="collapseTransactions" class="collapse <?php echo e(request()->is('*-transactions*') || request()->is('expenses*') ? 'show' : ''); ?>" aria-labelledby="headingTransactions"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo e(request()->routeIs('bank-transactions.index') ? 'active' : ''); ?>" href="<?php echo e(route('bank-transactions.index')); ?>">Bank Deposits</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('expenses.index') ? 'active' : ''); ?>" href="<?php echo e(route('expenses.index')); ?>">Expenses</a>
                        <a class="collapse-item <?php echo e(request()->routeIs('cash-transactions.index') ? 'active' : ''); ?>" href="<?php echo e(route('cash-transactions.index')); ?>">Payables/Receivables</a>
                    </div>
                </div>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">

                        <?php if(Session::has('original_admin_id')): ?>
                            <li class="nav-item">
                                <a class="nav-link text-success font-weight-bold" href="<?php echo e(route('admin.users.stop_impersonate')); ?>">
                                    <i class="fas fa-user-secret fa-sm fa-fw mr-2"></i>
                                    Return to Admin
                                </a>
                            </li>
                        <?php endif; ?>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo e(Auth::user()->name); ?></span>
                                <i class="fas fa-user-circle fa-2x"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="<?php echo e(route('profile.edit')); ?>">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    My Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <div class="container-fluid">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
                </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>
                            Developed by **BKR TECH** &copy; <?php echo e(date('Y')); ?> | Need help? 
                            Contact support at <?php echo e(\App\Models\LoanManager::getGlobalSupportPhone()); ?>

                        </span>
                    </div>
                </div>
            </footer>
            </div>
        </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class.blade.php="btn btn-primary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php echo $__env->yieldPushContent('modals'); ?>

    <script src="<?php echo e(asset('vendor/jquery/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>

    <script src="<?php echo e(asset('vendor/jquery-easing/jquery.easing.min.js')); ?>"></script>

    <script src="<?php echo e(asset('js/sb-admin-2.min.js')); ?>"></script>

    <?php echo $__env->yieldPushContent('scripts'); ?>

</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/layouts/app.blade.php ENDPATH**/ ?>