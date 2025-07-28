<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="card mb-4">
    <div class="card-header"><h4>Quick Actions</h4></div>
    <div class="card-body">
        <a href="{{ route('admin.broadcast.create') }}" class="btn btn-primary">Send Broadcast Message</a>
        </div>
</div>
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

        <div class="card mb-4">
            <div class="card-header">
                <h4>Loan Managers Awaiting Activation</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Address</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loanManagers->where('loanManager.is_active', false) as $manager)
                            <tr>
                                <td>{{ $manager->name }}</td>
                                <td>{{ $manager->email }}</td>
                                <td>{{ $manager->loanManager->phone_number ?? 'N/A' }}</td>
                                <td>{{ $manager->loanManager->address ?? 'N/A' }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.managers.activate', $manager->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No loan managers are currently awaiting activation.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Active Loan Managers</h4>
            </div>
            <div class="card-body">
                 <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Address</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loanManagers->where('loanManager.is_active', true) as $manager)
                            <tr>
                                <td>{{ $manager->name }}</td>
                                <td>{{ $manager->email }}</td>
                                <td>{{ $manager->loanManager->phone_number ?? 'N/A' }}</td>
                                <td>{{ $manager->loanManager->address ?? 'N/A' }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.managers.suspend', $manager->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">Suspend</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">There are no active loan managers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>