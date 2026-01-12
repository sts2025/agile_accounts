<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Loan Manager')</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body id="page-top">

    <div id="wrapper">

        {{-- SIDEBAR START --}}
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
                <div class="sidebar-brand-icon">
                    {{-- Ensure you have a logo or remove the image tag if not needed --}}
                    <img src="{{ asset('img/logo.png') }}" alt="Agile Accounts Logo" style="height: 40px;" onerror="this.style.display='none'">
                    <i class="fas fa-landmark" style="display:none;" onerror="this.style.display='block'"></i> {{-- Fallback Icon --}}
                </div>
                <div class="sidebar-brand-text mx-3">Agile Accounts</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Core Management
            </div>

            {{-- CLIENTS DROPDOWN (UPDATED) --}}
            <li class="nav-item {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseClients"
                    aria-expanded="true" aria-controls="collapseClients">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Client Management</span>
                </a>
                <div id="collapseClients" class="collapse {{ request()->routeIs('clients.*') ? 'show' : '' }}"
                    aria-labelledby="headingClients" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Actions:</h6>
                        <a class="collapse-item {{ request()->routeIs('clients.index') && !request()->has('filter') ? 'active' : '' }}" href="{{ route('clients.index') }}">All Clients</a>
                        <a class="collapse-item {{ request()->routeIs('clients.create') ? 'active' : '' }}" href="{{ route('clients.create') }}">Add New Client</a>
                        
                        <h6 class="collapse-header">Filters:</h6>
                        <a class="collapse-item {{ request()->input('filter') == 'not_paid' ? 'active' : '' }}" href="{{ route('clients.index', ['filter' => 'not_paid']) }}">Not Paid</a>
                        <a class="collapse-item {{ request()->input('filter') == 'with_loans' ? 'active' : '' }}" href="{{ route('clients.index', ['filter' => 'with_loans']) }}">With Loans</a>
                        <a class="collapse-item {{ request()->input('filter') == 'no_loans' ? 'active' : '' }}" href="{{ route('clients.index', ['filter' => 'no_loans']) }}">Without Loans</a>
                    </div>
                </div>
            </li>

            {{-- LOANS DROPDOWN (UPDATED) --}}
            <li class="nav-item {{ request()->routeIs('loans.*') ? 'active' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLoans"
                    aria-expanded="true" aria-controls="collapseLoans">
                    <i class="fas fa-fw fa-hand-holding-usd"></i>
                    <span>Loan Management</span>
                </a>
                <div id="collapseLoans" class="collapse {{ request()->routeIs('loans.*') ? 'show' : '' }}"
                    aria-labelledby="headingLoans" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Actions:</h6>
                        <a class="collapse-item {{ request()->routeIs('loans.index') && !request()->has('filter') ? 'active' : '' }}" href="{{ route('loans.index') }}">All Loans</a>
                        <a class="collapse-item {{ request()->routeIs('loans.create') ? 'active' : '' }}" href="{{ route('loans.create') }}">Create New Loan</a>
                        
                        {{-- THIS IS THE NEW FEATURE --}}
                        <a class="collapse-item {{ request()->input('filter') == 'completed' ? 'active' : '' }}" href="{{ route('loans.index', ['filter' => 'completed']) }}">Completed Loans</a>
                        
                        <div class="collapse-divider"></div>
                        <h6 class="collapse-header">Tools:</h6>
                        <a class="collapse-item {{ request()->routeIs('loans.showCalculator') ? 'active' : '' }}" href="{{ route('loans.showCalculator') }}">Loan Calculator</a>
                    </div>
                </div>
            </li>

            {{-- REPORTS DROPDOWN (EXISTING) --}}
            <li class="nav-item {{ request()->is('reports*') ? 'active' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports"
                    aria-expanded="true" aria-controls="collapseReports">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Reports</span>
                </a>
                <div id="collapseReports" class="collapse {{ request()->is('reports*') ? 'show' : '' }}"
                    aria-labelledby="headingReports" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item {{ request()->routeIs('reports.daily') ? 'active' : '' }}" href="{{ route('reports.daily') }}">Daily Report</a>
                        <a class="collapse-item {{ request()->routeIs('reports.general-ledger') ? 'active' : '' }}" href="{{ route('reports.general-ledger') }}">General Ledger</a>
                        <a class="collapse-item {{ request()->routeIs('reports.trial-balance') ? 'active' : '' }}" href="{{ route('reports.trial-balance') }}">Trial Balance</a>
                        <a class="collapse-item {{ request()->routeIs('reports.profit-and-loss') ? 'active' : '' }}" href="{{ route('reports.profit-and-loss') }}">P&L Statement</a>
                        <a class="collapse-item {{ request()->routeIs('reports.balance-sheet') ? 'active' : '' }}" href="{{ route('reports.balance-sheet') }}">Balance Sheet</a>
                        <a class="collapse-item {{ request()->routeIs('reports.loan-aging') ? 'active' : '' }}" href="{{ route('reports.loan-aging') }}">Loan Aging Report</a>
                    </div>
                </div>
            </li>

            {{-- TRANSACTIONS DROPDOWN (EXISTING) --}}
            <li class="nav-item {{ request()->is('*-transactions*') || request()->is('expenses*') ? 'active' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTransactions"
                    aria-expanded="true" aria-controls="collapseTransactions">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
                <div id="collapseTransactions" class="collapse {{ request()->is('*-transactions*') || request()->is('expenses*') ? 'show' : '' }}"
                    aria-labelledby="headingTransactions" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item {{ request()->routeIs('bank-transactions.index') ? 'active' : '' }}" href="{{ route('bank-transactions.index') }}">Bank Deposits</a>
                        <a class="collapse-item {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Expenses</a>
                        <a class="collapse-item {{ request()->routeIs('cash-transactions.index') ? 'active' : '' }}" href="{{ route('cash-transactions.index') }}">Payables/Receivables</a>
                    </div>
                </div>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        {{-- SIDEBAR END --}}

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                {{-- TOPBAR --}}
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">

                        @if (Session::has('original_admin_id'))
                            <li class="nav-item">
                                <a class="nav-link text-success font-weight-bold" href="{{ route('admin.users.stop_impersonate') }}">
                                    <i class="fas fa-user-secret fa-sm fa-fw mr-2"></i>
                                    Return to Admin
                                </a>
                            </li>
                        @endif

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->name ?? 'User' }}</span>
                                <i class="fas fa-user-circle fa-2x"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
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
                {{-- END TOPBAR --}}

                <div class="container-fluid">
                    @yield('content')
                </div>

            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>
                            Developed by <strong>BKR TECH</strong> &copy; {{ date('Y') }} | Need help? 
                            Contact support at {{ \App\Models\LoanManager::getGlobalSupportPhone() }}
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
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    @stack('modals')

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>

    @stack('scripts')

</body>
</html>