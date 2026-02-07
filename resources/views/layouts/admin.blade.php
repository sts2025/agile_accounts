<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>@yield('title', 'Admin Panel')</title>

    {{-- Fonts & Icons --}}
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    {{-- SB Admin 2 (Bootstrap 4) --}}
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.3/css/sb-admin-2.min.css" rel="stylesheet">

    {{-- Page level styles --}}
    @stack('styles')
</head>

<body id="page-top">

<div id="wrapper">

    {{-- ================= Sidebar ================= --}}
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        {{-- Brand --}}
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
            <div class="sidebar-brand-icon">
                <img src="{{ asset('img/logo.png') }}" alt="Agile Accounts" style="height:40px;">
            </div>
            <div class="sidebar-brand-text mx-3">Agile Accounts</div>
        </a>

        <hr class="sidebar-divider my-0">

        {{-- Dashboard --}}
        <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">Management</div>

        {{-- Managers --}}
        <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-fw fa-users-cog"></i>
                <span>Manage Managers</span>
            </a>
        </li>

        {{-- Broadcasts --}}
        <li class="nav-item {{ request()->routeIs('admin.broadcasts.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.broadcasts.index') }}">
                <i class="fas fa-fw fa-bullhorn"></i>
                <span>Broadcasts</span>
            </a>
        </li>

        <hr class="sidebar-divider d-none d-md-block">

        {{-- Toggle --}}
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

    </ul>
    {{-- ================= End Sidebar ================= --}}

    {{-- ================= Content Wrapper ================= --}}
    <div id="content-wrapper" class="d-flex flex-column">

        <div id="content">

            {{-- ================= Topbar ================= --}}
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <ul class="navbar-nav ml-auto">

                    {{-- Impersonation --}}
                    @if (Session::has('original_admin_id'))
                        <li class="nav-item">
                            <a class="nav-link text-danger font-weight-bold" href="{{ route('admin.users.stop_impersonate') }}">
                                <i class="fas fa-user-secret mr-2"></i>
                                Stop Impersonating
                            </a>
                        </li>
                    @endif

                    <div class="topbar-divider d-none d-sm-block"></div>

                    {{-- User --}}
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                {{ Auth::user()->name ?? 'Admin' }}
                            </span>
                            <i class="fas fa-user-shield fa-lg"></i>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                             aria-labelledby="userDropdown">

                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Logout
                            </a>
                        </div>
                    </li>

                </ul>
            </nav>
            {{-- ================= End Topbar ================= --}}

            {{-- ================= Page Content ================= --}}
            <div class="container-fluid">

                {{-- Page Heading --}}
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        @yield('page_heading', 'Admin Panel')
                    </h1>
                </div>

                {{-- Main Content --}}
                @yield('content')

            </div>
            {{-- ================= End Page Content ================= --}}

        </div>

        {{-- ================= Footer ================= --}}
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Developed by BKR TECH © {{ date('Y') }}</span>
                </div>
            </div>
        </footer>
        {{-- ================= End Footer ================= --}}

    </div>
    {{-- ================= End Content Wrapper ================= --}}

</div>

{{-- Scroll to Top --}}
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

{{-- ================= Logout Modal ================= --}}
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal">
                    <span>×</span>
                </button>
            </div>
            <div class="modal-body">
                Select "Logout" below if you are ready to end your session.
            </div>
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

{{-- ================= JS ================= --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.3/js/sb-admin-2.min.js"></script>

{{-- Page level scripts --}}
@yield('scripts')

</body>
</html>
