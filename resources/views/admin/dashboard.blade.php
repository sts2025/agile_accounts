@extends('layouts.admin')

@section('title', 'Admin Dashboard')

{{-- Use page_heading for the main title that appears in the content area --}}
@section('page_heading', 'Admin Panel') 

@section('content')
    
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Admin Actions Section --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manage Loan Managers</h6>
            <p class="m-0 text-secondary">Activate or suspend managers and set currency/support contacts.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{-- Changed table classes to align with SB Admin 2 / Bootstrap 4 standards --}}
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Currency</th>
                            <th>Support Phone</th>
                            <th class="text-center" style="min-width: 300px;">Actions / Settings</th>
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
                                        {{-- Used badge-success instead of bg-success --}}
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        {{-- Used badge-secondary instead of bg-secondary --}}
                                        <span class="badge badge-secondary">Inactive / Pending</span>
                                    @endif
                                </td>
                                <td>{{ $loanManager->currency_symbol ?? 'N/A' }}</td>
                                <td>{{ $loanManager->support_phone ?? 'N/A' }}</td>
                                
                                {{-- ACTIONS COLUMN --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center">
                                        @if ($isActive)
                                            {{-- Actions for ACTIVE managers --}}
                                            <a href="{{ route('admin.users.impersonate', $manager->id) }}" class="btn btn-info btn-sm mr-2">Login As</a>

                                            {{-- Suspend Button (You should use a POST method here for security, but sticking to your current GET route for now) --}}
                                            <a href="{{ route('admin.managers.suspend', $manager->id) }}" 
                                                class="btn btn-warning btn-sm"
                                                onclick="return confirm('Are you sure you want to suspend this manager?');">
                                                Suspend
                                            </a>
                                        @else
                                            {{-- Activation Form --}}
                                            {{-- Ensure POST method is used for updates --}}
                                            <form method="POST" action="{{ route('admin.managers.update', $manager->id) }}" class="form-inline">
                                                @csrf
                                                @method('PUT') {{-- Assuming you are using PUT/PATCH for updates --}}
                                                
                                                {{-- Hidden activation toggle --}}
                                                <input type="hidden" name="is_active" value="1">
                                                
                                                {{-- Currency Dropdown --}}
                                                <select name="currency_symbol" class="form-control form-control-sm mr-2" style="width: 100px;" required>
                                                    <option value="" disabled selected>Currency</option>
                                                    <option value="UGX">UGX</option>
                                                    <option value="RWF">RWF</option>
                                                </select>
                                                
                                                {{-- Support Phone Input --}}
                                                <input type="text" name="support_phone" class="form-control form-control-sm mr-2" placeholder="Support Phone" style="width: 150px;" required>
                                                
                                                {{-- Activate Button --}}
                                                <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                            </form>
                                        @endif
                                    </div>
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
@endsection