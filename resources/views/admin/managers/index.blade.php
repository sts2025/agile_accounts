@extends('layouts.app')

@section('title', 'Manage Managers')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Manage Loan Managers</h1>
    <p class="mb-4">Activate pending accounts, set currencies, suspend access, or delete managers.</p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Registered Accounts</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 15%">Name</th>
                            <th style="width: 20%">Email</th>
                            <th style="width: 10%">Status</th>
                            <th style="width: 10%">Currency</th>
                            <th style="width: 20%">Support Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($managers as $user)
                        @php 
                            $lm = $user->loanManager; 
                            // Check active status by presence of currency_symbol
                            $isActive = $lm && !empty($lm->currency_symbol);
                        @endphp
                        <tr>
                            {{-- 1. Name --}}
                            <td class="align-middle font-weight-bold text-dark">{{ $user->name }}</td>
                            
                            {{-- 2. Email --}}
                            <td class="align-middle">{{ $user->email }}</td>
                            
                            {{-- 3. Status --}}
                            <td class="align-middle">
                                @if($isActive)
                                    <span class="badge badge-success px-2 py-1">Active</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">Inactive</span>
                                @endif
                            </td>

                            {{-- LOGIC: ACTIVE USER --}}
                            @if($isActive)
                                <td class="align-middle">{{ $lm->currency_symbol }}</td>
                                <td class="align-middle">{{ $lm->support_phone }}</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        {{-- LOGIN AS (Text Button) --}}
                                        <a href="{{ route('admin.users.impersonate', $user->id) }}" class="btn btn-info btn-sm text-white mr-1 shadow-sm">
                                            Login As
                                        </a>

                                        {{-- SUSPEND (Text Button) --}}
                                        <form action="{{ route('admin.managers.suspend', $user->id) }}" method="POST" class="mr-1">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm text-dark shadow-sm">Suspend</button>
                                        </form>

                                        {{-- DELETE (Icon Button) --}}
                                        <form action="{{ route('admin.managers.destroy', $user->id) }}" method="POST" onsubmit="return confirm('PERMANENT DELETE: Are you sure?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm shadow-sm" title="Delete Permanently">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        {{-- Edit Icon (Subtle) --}}
                                        <button type="button" class="btn btn-link btn-sm text-primary ml-1" data-toggle="modal" data-target="#edit{{$user->id}}" title="Edit Settings">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>

                                    {{-- Edit Modal --}}
                                    <div class="modal fade" id="edit{{$user->id}}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.managers.update', $user->id) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Manager</h5>
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Currency</label>
                                                            <select name="currency" class="form-control">
                                                                @foreach($currencies as $curr)
                                                                    <option value="{{ $curr }}" {{ $lm->currency_symbol == $curr ? 'selected' : '' }}>{{ $curr }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Phone</label>
                                                            <input type="text" name="support_phone" class="form-control" value="{{ $lm->support_phone }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                            {{-- LOGIC: PENDING USER --}}
                            @else
                                <form action="{{ route('admin.managers.activate', $user->id) }}" method="POST">
                                    @csrf
                                    <td class="align-middle p-2">
                                        <select name="currency" class="form-control form-control-sm">
                                            @foreach($currencies as $curr)
                                                <option value="{{ $curr }}">{{ $curr }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="align-middle p-2">
                                        <input type="text" name="support_phone" class="form-control form-control-sm" placeholder="Phone...">
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            {{-- ACTIVATE (Text Button) --}}
                                            <button type="submit" class="btn btn-success btn-sm mr-2 shadow-sm">
                                                Activate
                                            </button>

                                            {{-- DELETE (Icon Button) --}}
                                            <button type="submit" form="del{{$user->id}}" class="btn btn-danger btn-sm shadow-sm" title="Delete Request">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                                {{-- Separate Delete Form --}}
                                <form id="del{{$user->id}}" action="{{ route('admin.managers.destroy', $user->id) }}" method="POST" class="d-none" onsubmit="return confirm('Delete this user?');">
                                    @csrf @method('DELETE')
                                </form>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection