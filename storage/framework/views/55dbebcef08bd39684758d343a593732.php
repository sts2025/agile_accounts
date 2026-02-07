<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="description" content="Loan Management System by STREAMLINE TECH SOLUTION">
    
    <base href="<?php echo e(url('/')); ?>/">

    <title><?php echo $__env->yieldContent('title', 'Agile Accounts'); ?></title>

    
    <link href="<?php echo e(asset('vendor/fontawesome-free/css/all.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    
    <link href="<?php echo e(asset('css/sb-admin-2.min.css')); ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet" onerror="this.remove()">

    <style>
        /* === EXTREME CSS GUARDRAIL === */
        /* Forces any backdrop element to be invisible, tiny, and unclickable */
        .modal-backdrop, .fade.show, .sidebar-backdrop {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
            position: absolute !important;
            z-index: -9999 !important;
        }
        
        /* Force body to be scrollable and clickable */
        body, html, #wrapper, #content-wrapper {
            overflow: auto !important;
            height: auto !important;
            padding-right: 0 !important;
            pointer-events: auto !important;
        }
        
        body.modal-open {
            overflow: auto !important;
        }
    </style>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body id="page-top">

    <div id="wrapper">

        
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo e(route('dashboard')); ?>">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-landmark"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Agile Accounts</div>
            </a>

            <hr class="sidebar-divider my-0">

            <?php if(request()->is('admin*')): ?>
                
                
                <li class="nav-item <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
                    <a class="nav-link" href="<?php echo e(route('admin.dashboard')); ?>">
                        <i class="fas fa-fw fa-tachometer-alt"></i>
                        <span>Admin Dashboard</span>
                    </a>
                </li>

                <div class="sidebar-heading mt-3">Administration</div>

                <li class="nav-item <?php echo e(request()->routeIs('admin.managers.*') ? 'active' : ''); ?>">
                    <a class="nav-link" href="<?php echo e(route('admin.dashboard')); ?>">
                        <i class="fas fa-fw fa-users-cog"></i>
                        <span>Manage Managers</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e(request()->routeIs('admin.broadcasts.*') ? 'active' : ''); ?>">
                    <a class="nav-link" href="<?php echo e(route('admin.broadcasts.index')); ?>">
                        <i class="fas fa-fw fa-bullhorn"></i>
                        <span>Broadcasts</span>
                    </a>
                </li>

                <hr class="sidebar-divider">

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(route('dashboard')); ?>">
                        <i class="fas fa-fw fa-arrow-left"></i>
                        <span>Exit Admin Panel</span>
                    </a>
                </li>

            <?php else: ?>

                

                <li class="nav-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                    <a class="nav-link" href="<?php echo e(route('dashboard')); ?>">
                        <i class="fas fa-fw fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item <?php echo e(request()->routeIs('payments.*') ? 'active' : ''); ?>">
                    <a class="nav-link" href="<?php echo e(route('payments.index')); ?>">
                        <i class="fas fa-fw fa-money-bill-wave"></i>
                        <span>All Payments</span>
                    </a>
                </li>

                <?php if(auth()->id() === 1 || session()->has('original_admin_id')): ?>
                <li class="nav-item">
                    <a class="nav-link bg-danger text-white font-weight-bold" href="<?php echo e(route('admin.dashboard')); ?>">
                        <i class="fas fa-fw fa-user-shield text-white"></i>
                        <span>Go to Admin Panel</span>
                    </a>
                </li>
                <?php endif; ?>

                <hr class="sidebar-divider">

                <div class="sidebar-heading">Core</div>

                
                <li class="nav-item <?php echo e(request()->routeIs('clients.*') ? 'active' : ''); ?>">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseClients" aria-expanded="true">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Clients</span>
                    </a>
                    <div id="collapseClients" class="collapse <?php echo e(request()->routeIs('clients.*') ? 'show' : ''); ?>" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="<?php echo e(route('clients.index')); ?>">All Clients</a>
                            <a class="collapse-item" href="<?php echo e(route('clients.create')); ?>">Add Client</a>
                        </div>
                    </div>
                </li>

                
                <li class="nav-item <?php echo e(request()->routeIs('loans.*') ? 'active' : ''); ?>">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLoans" aria-expanded="true">
                        <i class="fas fa-fw fa-hand-holding-usd"></i>
                        <span>Loans</span>
                    </a>
                    <div id="collapseLoans" class="collapse <?php echo e(request()->routeIs('loans.*') ? 'show' : ''); ?>" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="<?php echo e(route('loans.index')); ?>">Active Loans</a>
                            <a class="collapse-item" href="<?php echo e(route('loans.create')); ?>">New Loan</a>
                            <a class="collapse-item" href="<?php echo e(route('loans.index', ['filter' => 'completed'])); ?>">Completed</a>
                            <a class="collapse-item" href="<?php echo e(route('loans.showCalculator')); ?>">Calculator</a>
                        </div>
                    </div>
                </li>

                <div class="sidebar-heading mt-2">Finance</div>

                
                <li class="nav-item <?php echo e(request()->is('*-transactions*') || request()->is('expenses*') ? 'active' : ''); ?>">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTx" aria-expanded="true">
                        <i class="fas fa-fw fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                    <div id="collapseTx" class="collapse <?php echo e(request()->is('*-transactions*') || request()->is('expenses*') ? 'show' : ''); ?>" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="<?php echo e(route('bank-transactions.index')); ?>">Banking</a>
                            <a class="collapse-item" href="<?php echo e(route('expenses.index')); ?>">Expenses</a>
                            <a class="collapse-item" href="<?php echo e(route('cash-transactions.index')); ?>">Payable/Receivable</a>
                        </div>
                    </div>
                </li>

                
                <li class="nav-item <?php echo e(request()->is('reports*') ? 'active' : ''); ?>">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports" aria-expanded="true">
                        <i class="fas fa-fw fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                    <div id="collapseReports" class="collapse <?php echo e(request()->is('reports*') ? 'show' : ''); ?>" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="<?php echo e(route('reports.daily')); ?>">Daily Report</a>
                            <a class="collapse-item" href="<?php echo e(route('reports.profit-and-loss')); ?>">Profit & Loss</a>
                            <a class="collapse-item" href="<?php echo e(route('reports.balance-sheet')); ?>">Balance Sheet</a>
                            <a class="collapse-item" href="<?php echo e(route('reports.trial-balance')); ?>">Trial Balance</a>
                            <a class="collapse-item" href="<?php echo e(route('reports.general-ledger')); ?>">General Ledger</a>
                        </div>
                    </div>
                </li>

                <hr class="sidebar-divider">
                <div class="sidebar-heading">Settings</div>

                <li class="nav-item <?php echo e(request()->routeIs('profile.edit') ? 'active' : ''); ?>">
                    <a class="nav-link" href="<?php echo e(route('profile.edit')); ?>">
                        <i class="fas fa-fw fa-cogs"></i>
                        <span>Business Settings</span>
                    </a>
                </li>

            <?php endif; ?>

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

                    <h5 class="m-0 font-weight-bold text-primary ml-2 d-none d-sm-block">
                        <?php if(request()->is('admin*')): ?>
                            <span class="badge badge-danger px-2">ADMIN PANEL</span>
                        <?php endif; ?>
                    </h5>

                    <ul class="navbar-nav ml-auto">
                        <?php if(Session::has('original_admin_id')): ?>
                            <li class="nav-item">
                                <a class="nav-link text-danger font-weight-bold" href="<?php echo e(route('admin.users.stop_impersonate')); ?>">
                                    <i class="fas fa-user-secret mr-2"></i> Stop Login As
                                </a>
                            </li>
                        <?php endif; ?>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo e(Auth::user()->name); ?></span>
                                <i class="fas fa-user-circle fa-2x"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="<?php echo e(route('profile.edit')); ?>">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
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
                        <span>Developed by <strong>BKR TECH</strong> &copy; <?php echo e(date('Y')); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-primary">Logout</button>
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

    <script>
        /* === PERMANENT BACKDROP KILLER (Turbo Mode) === */
        function forceUnlockScreen() {
            // 1. Remove ANY element with 'backdrop' in its class name
            const backdrops = document.querySelectorAll('[class*="backdrop"]');
            if (backdrops.length > 0) {
                backdrops.forEach(b => b.remove());
            }

            // 2. Unlock the body classes
            if (document.body.classList.contains('modal-open')) {
                document.body.classList.remove('modal-open');
            }
            
            // 3. Force CSS properties to ensure clickability
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '0px';
            document.body.style.pointerEvents = 'auto';
        }

        // Run immediately on DOM load
        document.addEventListener("DOMContentLoaded", forceUnlockScreen);
        
        // Run on window load
        window.onload = forceUnlockScreen;
        
        // Run aggressively every 100ms forever to catch late script injections
        setInterval(forceUnlockScreen, 100);

        // Run on every click to ensure UI stays unlocked
        document.addEventListener('click', forceUnlockScreen);
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/layouts/app.blade.php ENDPATH**/ ?>