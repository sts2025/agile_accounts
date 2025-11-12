<?php
// Note: This file uses the \App\Models\LoanManager::getGlobalSupportPhone() helper
// to display the support number set by the Admin.
?>
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
        .sidebar { width: 280px; min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; top: 0; left: 0; }
        .sidebar .logo { text-align: center; margin-bottom: 30px; }
        .sidebar .logo img { height: 40px; }
        .sidebar .nav-link { color: #e1e8ec; padding: 10px 15px; border-radius: 5px; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #34495e; color: white; }
        .sidebar .nav-link[data-bs-toggle="collapse"]::after { content: ' ▸'; float: right; }
        .sidebar .nav-link[data-bs-toggle="collapse"][aria-expanded="true"]::after { content: ' ▾'; }
        .sidebar .collapse .nav-link { font-size: 0.9em; padding-left: 20px; }
        .main-content { margin-left: 280px; padding: 30px; width: calc(100% - 280px); display: flex; flex-direction: column; min-height: 100vh; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .top-menu { display: flex; align-items: center; gap: 15px; }
        .top-menu a.nav-link { color: #2c3e50; font-weight: 500; text-decoration: none; }
        .top-menu a.nav-link:hover { text-decoration: underline; }
        .icon-card { transition: all 0.3s ease; }
        .icon-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .content-wrapper { flex-grow: 1; }
        .app-footer { padding-top: 20px; text-align: center; border-top: 1px solid #ddd; margin-top: auto; font-size: 0.85rem; color: #6c757d; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('AGILE ACCOUNTS.jpg') }}" alt="Agile Accounts Logo">
            </a>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"> <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"> <i class="bi bi-grid-fill me-2"></i> Dashboard </a> </li>
            <li class="nav-item"> <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}"> <i class="bi bi-people-fill me-2"></i> My Clients </a> </li>
            
            {{-- LOANS SECTION START --}}
            <li class="nav-item"> 
                <a class="nav-link {{ request()->routeIs('loans.index') ? 'active' : '' }}" href="{{ route('loans.index') }}"> 
                    <i class="bi bi-journal-text me-2"></i> My Loans 
                </a> 
            </li>
            
            {{-- *** NEW LOAN CALCULATOR LINK INSERTED HERE *** --}}
            <li class="nav-item"> 
                <a class="nav-link {{ request()->routeIs('loans.showCalculator') ? 'active' : '' }}" href="{{ route('loans.showCalculator') }}"> 
                    <i class="fas fa-calculator me-2"></i> Loan Calculator 
                </a> 
            </li>
            {{-- LOANS SECTION END --}}
            
            {{-- *** REPORTS SUBMENU FIX: All routes are now 'reports.name' *** --}}
            <li class="nav-item">
                <a class="nav-link collapsed" href="#reports-submenu" data-bs-toggle="collapse" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}"> <i class="bi bi-bar-chart-fill me-2"></i> Reports </a>
                <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reports-submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.daily') ? 'active' : '' }}" href="{{ route('reports.daily') }}">Daily Report</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.general-ledger') ? 'active' : '' }}" href="{{ route('reports.general-ledger') }}">General Ledger</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.trial-balance') ? 'active' : '' }}" href="{{ route('reports.trial-balance') }}">Trial Balance</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.profit-and-loss') ? 'active' : '' }}" href="{{ route('reports.profit-and-loss') }}">P&L Statement</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.balance-sheet') ? 'active' : '' }}" href="{{ route('reports.balance-sheet') }}">Balance Sheet</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.loan-aging') ? 'active' : '' }}" href="{{ route('reports.loan-aging') }}">Loan Aging Report</a></li>
                    </ul>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#transactions-submenu" data-bs-toggle="collapse" aria-expanded="{{ request()->routeIs(['bank-transactions.*', 'expenses.*', 'cash-transactions.*']) ? 'true' : 'false' }}"> <i class="bi bi-arrow-down-up me-2"></i> Transactions </a>
                <div class="collapse {{ request()->routeIs(['bank-transactions.*', 'expenses.*', 'cash-transactions.*']) ? 'show' : '' }}" id="transactions-submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"> <a class="nav-link {{ request()->routeIs('bank-transactions.*') ? 'active' : '' }}" href="{{ route('bank-transactions.index') }}"> <i class="fas fa-fw fa-university"></i> <span>Bank Deposits</span></a> </li>
                        <li class="nav-item"> <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"> <i class="fas fa-fw fa-file-invoice"></i> <span>Expenses</span></a> </li>
                        <li class="nav-item"> <a class="nav-link {{ request()->routeIs('cash-transactions.*') ? 'active' : '' }}" href="{{ route('cash-transactions.index') }}"> <i class="fas fa-fw fa-hand-holding-usd"></i> <span>Payables/Receivables</span></a> </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="main-header">
            <h4 class="mb-0">@yield('title')</h4>
            <div class="top-menu">
                <a class="nav-link" href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-circle me-1"></i> My Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
        
        <div class="content-wrapper">
            <main>
                {{-- *** BROADCAST MESSAGE DISPLAY *** --}}
                @if (isset($broadcastMessage))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading">{{ $broadcastMessage->title }}</h5>
                    <p>{{ $broadcastMessage->body }}</p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                {{-- *** END BROADCAST MESSAGE *** --}}

                @yield('content') {{-- *** THE SINGLE, CORRECT YIELD *** --}}
            </main>
        </div>

        {{-- *** DYNAMIC FOOTER FIX *** --}}
        <footer class="app-footer">
            Developed by **BKR TECH** &copy; {{ date('Y') }} | Need help? Contact support at 
            {{-- Uses the dynamic helper to fetch the phone number assigned to this manager --}}
            <a href="tel:{{ \App\Models\LoanManager::getGlobalSupportPhone() }}" class="fw-bold text-decoration-none">
                {{ \App\Models\LoanManager::getGlobalSupportPhone() }}
            </a>
        </footer>
        {{-- *** END DYNAMIC FOOTER FIX *** --}}
    </div>

    @stack('modals')
    @stack('scripts')
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>
