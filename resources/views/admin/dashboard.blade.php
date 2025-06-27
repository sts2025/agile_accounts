<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Admin Dashboard</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>
        
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Managers</h5>
                        <p class="card-text fs-4">{{ $loanManagerCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title">Total Clients</h5>
                        <p class="card-text fs-4">{{ $clientCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Loans</h5>
                        <p class="card-text fs-4">{{ $totalLoans }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Loaned</h5>
                        <p class="card-text fs-4">UGX {{ number_format($totalLoanedAmount, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
<div class="card mb-4">
            <div class="card-header">
                <h4>Financial Reports</h4>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.reports.trial-balance') }}" class="btn btn-secondary">View Trial Balance</a>
                 <a href="{{ route('admin.reports.profit-and-loss') }}" class="btn btn-secondary">View Profit & Loss</a>
                 <a href="{{ route('admin.reports.balance-sheet') }}" class="btn btn-secondary">View Balance Sheet</a>
            </div>
            </div>
                </div>
        </div>

        <div class="card">
        ```


        <div class="card">
            <div class="card-header">
                <h4>Manage Loan Managers</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loanManagers as $manager)
                            <tr>
                                <td>{{ $manager->name }}</td>
                                <td>{{ $manager->email }}</td>
                                <td>
                                    @if ($manager->loanManager->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($manager->loanManager->is_active)
                                        <form method="POST" action="{{ route('admin.managers.suspend', $manager->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm">Suspend</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.managers.activate', $manager->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">There are no loan managers in the system.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
            </div>
        </div>
    </div>
</body>
</html>