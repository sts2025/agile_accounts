<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Agile Accounts')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; background-color: #f4f7f6; }
        .sidebar { width: 280px; min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; top: 0; left: 0; }
        .sidebar .logo { text-align: center; margin-bottom: 30px; }
        .sidebar .logo img { height: 40px; }
        .sidebar .nav-link { color: #bdc3c7; padding: 10px 15px; border-radius: 5px; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 280px; padding: 30px; width: calc(100% - 280px); }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="{{ route('dashboard') }}"><img src="/images/logo.jpg" alt="Agile Accounts Logo"></a>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
           
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">My Loans</a></li>
            <li class="nav-item mt-3"><h6 class="text-muted ps-3">Reports</h6></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('manager.reports.daily-report') ? 'active' : '' }}" href="{{ route('manager.reports.daily-report') }}">Daily Report</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('manager.reports.trial-balance') ? 'active' : '' }}" href="{{ route('manager.reports.trial-balance') }}">Trial Balance</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('manager.reports.profit-and-loss') ? 'active' : '' }}" href="{{ route('manager.reports.profit-and-loss') }}">P&L Statement</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('manager.reports.balance-sheet') ? 'active' : '' }}" href="{{ route('manager.reports.balance-sheet') }}">Balance Sheet</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('manager.reports.aging-analysis') ? 'active' : '' }}" href="{{ route('manager.reports.aging-analysis') }}">Loan Aging Report</a></li>

            <li class="nav-item mt-3"><h6 class="text-muted ps-3 text-uppercase small">Transactions</h6></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('banking.index') ? 'active' : '' }}" href="{{ route('banking.index') }}">Bank Deposits</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('cash-transfers.index') ? 'active' : '' }}" href="{{ route('cash-transfers.index') }}">Payables/Receivables</a></li>

        </ul>
    </div>

    <div class="main-content">
        <div class="main-header">
            <h4>@yield('title')</h4>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>
        <main>
            @yield('content')
        </main>
    </div>

    @stack('modals')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>