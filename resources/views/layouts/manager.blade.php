<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Agile Accounts')</title>
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
    @stack('styles')
</head>
<body>
    <div class="sidebar shadow">
        <div class="logo">
            <a href="{{ route('dashboard') }}" class="text-decoration-none">
                {{-- SIDEBAR BRANDING --}}
                <h4 class="text-white mb-0 fw-bold">
                    {{ optional(Auth::user()->getCompany())->company_name ?? 'Agile Accounts' }}
                </h4>
                <small class="text-info" style="font-size: 0.7rem; letter-spacing: 1px;">MANAGEMENT PORTAL</small>
            </a>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item"> 
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"> 
                    <i class="bi bi-grid-fill me-2"></i> Dashboard 
                </a> 
            </li>

            <li class="nav-item"> 
                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}" style="background-color: #3498db; color: white; margin-bottom: 15px;"> 
                    <i class="bi bi-cash-stack me-2"></i> All Payments 
                </a> 
            </li>

            <div class="sidebar-sub-header">Operations</div>

            {{-- CLIENTS --}}
            <li class="nav-item">
                <a class="nav-link collapsed" href="#clients-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-people-fill me-2"></i> Clients 
                </a>
                <div class="collapse {{ request()->routeIs('clients.*') ? 'show' : '' }}" id="clients-submenu">
                    <ul class="nav flex-column ms-2">
                        <div class="submenu-header">Actions</div>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('clients.index') && !request()->has('filter') ? 'active' : '' }}" href="{{ route('clients.index') }}">List Clients</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('clients.create') ? 'active' : '' }}" href="{{ route('clients.create') }}">Add New Client</a></li>
                        
                        <div class="submenu-header">Filters</div>
                        <li class="nav-item"><a class="nav-link {{ request()->input('filter') == 'not_paid' ? 'active' : '' }}" href="{{ route('clients.index', ['filter' => 'not_paid']) }}">Not Paid</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->input('filter') == 'with_loans' ? 'active' : '' }}" href="{{ route('clients.index', ['filter' => 'with_loans']) }}">With Loans</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->input('filter') == 'no_loans' ? 'active' : '' }}" href="{{ route('clients.index', ['filter' => 'no_loans']) }}">Without Loans</a></li>
                    </ul>
                </div>
            </li>
            
            {{-- LOANS --}}
            <li class="nav-item"> 
                <a class="nav-link collapsed" href="#loans-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-journal-text me-2"></i> Loans 
                </a> 
                <div class="collapse {{ request()->routeIs('loans.*') ? 'show' : '' }}" id="loans-submenu">
                    <ul class="nav flex-column ms-2">
                        <div class="submenu-header">Actions</div>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('loans.index') && !request()->has('filter') ? 'active' : '' }}" href="{{ route('loans.index') }}">All Loans</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('loans.create') ? 'active' : '' }}" href="{{ route('loans.create') }}">Create Loan</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('loans.index', ['filter' => 'completed']) ? 'active' : '' }}" href="{{ route('loans.index', ['filter' => 'completed']) }}">Completed Loans</a></li>
                        
                        <div class="submenu-header">Tools</div>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('loans.showCalculator') ? 'active' : '' }}" href="{{ route('loans.showCalculator') }}">Loan Calculator</a></li>
                    </ul>
                </div>
            </li>
            
            {{-- REPORTS --}}
            <li class="nav-item">
                <a class="nav-link collapsed" href="#reports-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-bar-chart-fill me-2"></i> Reports 
                </a>
                <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reports-submenu">
                    <ul class="nav flex-column ms-2">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.daily') ? 'active' : '' }}" href="{{ route('reports.daily') }}">Daily Report</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.general-ledger') ? 'active' : '' }}" href="{{ route('reports.general-ledger') }}">General Ledger</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.trial-balance') ? 'active' : '' }}" href="{{ route('reports.trial-balance') }}">Trial Balance</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.profit-and-loss') ? 'active' : '' }}" href="{{ route('reports.profit-and-loss') }}">P&L Statement</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.balance-sheet') ? 'active' : '' }}" href="{{ route('reports.balance-sheet') }}">Balance Sheet</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.loan-aging') ? 'active' : '' }}" href="{{ route('reports.loan-aging') }}">Loan Aging Report</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.print-forms') ? 'active' : '' }}" href="{{ route('reports.print-forms') }}">Print Forms</a></li>
                    </ul>
                </div>
            </li>
            
            {{-- TRANSACTIONS --}}
            <li class="nav-item">
                <a class="nav-link collapsed" href="#transactions-submenu" data-bs-toggle="collapse"> 
                    <i class="bi bi-arrow-down-up me-2"></i> Transactions 
                </a>
                <div class="collapse {{ request()->routeIs(['bank-transactions.*', 'expenses.*', 'cash-transactions.*']) ? 'show' : '' }}" id="transactions-submenu">
                    <ul class="nav flex-column ms-2">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('bank-transactions.index') ? 'active' : '' }}" href="{{ route('bank-transactions.index') }}">
                            <i class="fas fa-university me-2"></i> Bank Deposits
                        </a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                            <i class="fas fa-file-invoice me-2"></i> Expenses
                        </a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('cash-transactions.index') ? 'active' : '' }}" href="{{ route('cash-transactions.index') }}">
                            <i class="fas fa-hand-holding-usd me-2"></i> Cash Flow
                        </a></li>
                    </ul>
                </div>
            </li>

            <div class="sidebar-sub-header">Administration</div>
            
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('manager.staff.*') ? 'active' : '' }}" href="{{ route('manager.staff.index') }}">
                    <i class="bi bi-person-badge-fill me-2"></i> Manage Staff
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('manager.settings.*') ? 'active' : '' }}" href="{{ route('manager.settings.edit') }}">
                    <i class="bi bi-gear-wide-connected me-2"></i> Business Settings
                </a>
            </li>

        </ul>
    </div>

    <div class="main-content">
        <div class="main-header bg-white px-3 rounded shadow-sm">
            {{-- HEADER: Company Name --}}
            <h5 class="mb-0 text-dark fw-bold text-uppercase">
                {{ optional(Auth::user()->getCompany())->company_name ?? 'Agile Accounts' }}
            </h5>
            
            <div class="top-menu">
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-md-block">
                        <div class="fw-bold small text-dark">{{ Auth::user()->name }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            {{ Auth::user()->role == 'cashier' ? 'Cashier' : 'Loan Manager' }}
                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-wrapper">
            @yield('content')
        </div>

        <footer class="app-footer">
            Developed by <strong>BKR TECH </strong> &copy; {{ date('Y') }} | 
            Support: <a href="tel:{{ \App\Models\LoanManager::getGlobalSupportPhone() }}" class="fw-bold text-decoration-none">
                {{ \App\Models\LoanManager::getGlobalSupportPhone() }}
            </a>
        </footer>
    </div>

    @stack('modals')
    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    {{-- ADVANCED BACKDROP KILLER --}}
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
</html>