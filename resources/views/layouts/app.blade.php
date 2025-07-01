<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Agile Accounts')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Agile Accounts Logo" style="height: 35px;">
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('clients.index') }}">My Clients</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('loans.index') }}">My Loans</a></li>
                </ul>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        @yield('content')
    </main>

    <footer class="container text-center mt-5 py-3 text-muted border-top">
        <p>Developed by <a href="https://www.stsug.site" target="_blank" rel="noopener noreferrer">BKR TECH</a> &copy; {{ now()->year }} | Need help? Contact support at <strong>0740859082</strong></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('modals') 
</body>
</html>