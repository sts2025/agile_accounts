@extends('layouts.app') {{-- <-- 1. FINAL FIX: Changed back to 'layouts.app' --}}

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid"> {{-- <-- 2. FIX: Added container-fluid for correct padding --}}
    
    <h1 class="h3 mb-4 text-gray-800">Admin Panel</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Admin Actions Section --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h4 class="m-0 font-weight-bold text-primary">Manage Loan Managers</h4>
            <p class="m-0 text-muted">Activate or suspend managers and set currency/support contacts.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Currency</th>
                            <th>Support Phone</th>
                            <th class="text-center" style="min-width: 350px;">Actions / Settings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($managers as $manager)
                            @php
                                $loanManager = $manager->loanManager;
                                $isActive = $loanManager && $loanManager->is_active;
                            @endphp
                            <tr>
                                <td>{{ $manager->name }}</td>
                                <td>{{ $manager->email }}</td>
                                <td>
                                    @if ($isActive)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive / Pending</span>
                                    @endif
                                </td>
                                <td>{{ $loanManager->currency_symbol ?? 'N/A' }}</td>
                                <td>{{ $loanManager->support_phone ?? 'N/A' }}</td>
                                
                                {{-- ACTIONS COLUMN --}}
                                <td class="text-center">
                                    @if ($isActive)
                                        {{-- Actions for ACTIVE managers --}}
                                        <a href="{{ route('admin.users.impersonate', $manager->id) }}" class="btn btn-info btn-sm me-2">Login As</a>

                                        {{-- Suspend Button (GET request) --}}
                                        <a href="{{ route('admin.managers.suspend', $manager->id) }}" 
                                           class="btn btn-warning btn-sm"
                                           onclick="return confirm('Are you sure you want to suspend this manager?');">
                                            Suspend
                                        </a>
                                    @else
                                        {{-- Activation Form --}}
                                        <form method="POST" action="{{ route('admin.managers.update', $manager->id) }}" class="row g-1 align-items-center justify-content-center">
                                            @csrf
                                            
                                            {{-- Hidden activation toggle --}}
                                            <input type="hidden" name="is_active" value="1">
                                            
                                            {{-- Currency Dropdown --}}
                                            <div class="col-3">
                                                <select name="currency_symbol" class="form-select form-select-sm" required>
                                                    <option value="" disabled selected>Currency</option>
                                                    <option value="UGX">UGX</option>
                                                    <option value="RWF">RWF</option>
                                                </select>
                                            </div>
                                            
                                            {{-- Support Phone Input --}}
                                            <div class="col-4">
                                                <input type="text" name="support_phone" class="form-control form-control-sm" placeholder="Support Phone" required>
                                            </div>
                                            
                                            {{-- Activate Button --}}
                                            <div class="col-3">
                                                <button type="submit" class="btn btn-success btn-sm w-100">Activate</button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No loan managers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection