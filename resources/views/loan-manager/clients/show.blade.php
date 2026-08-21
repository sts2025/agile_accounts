@extends('layouts.app')

@section('title', 'Client Details - ' . $client->name)

@section('content')
<div class="container-fluid">

    {{-- Page Heading --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Client Profile: {{ $client->name }}
            @if($client->client_type === 'business')
                <span class="badge badge-info" style="font-size:0.5em; vertical-align:middle;">BUSINESS{{ $client->business_name ? ' — ' . $client->business_name : '' }}</span>
            @endif
            @if($client->is_blacklisted)
                <span class="badge badge-dark" style="font-size:0.5em; vertical-align:middle;">BLACKLISTED</span>
            @endif
            @if($client->groups->isNotEmpty())
                <span class="badge badge-primary" style="font-size:0.5em; vertical-align:middle;">{{ $client->groups->pluck('name')->join(', ') }}</span>
            @endif
        </h1>
        <div class="no-print">
            <button class="btn btn-sm btn-outline-secondary shadow-sm" type="button" data-toggle="collapse" data-target="#convertTypeForm">
                Convert to {{ $client->client_type === 'business' ? 'Individual' : 'Business' }}
            </button>
            <button class="btn btn-sm btn-outline-primary shadow-sm" type="button" data-toggle="collapse" data-target="#transferGroupForm">Move to Another Group</button>
            @if($client->is_blacklisted)
                <form action="{{ route('clients.unblacklist', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove {{ $client->name }} from the blacklist?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success shadow-sm">Remove from Blacklist</button>
                </form>
            @else
                <button class="btn btn-sm btn-outline-dark shadow-sm" type="button" data-toggle="collapse" data-target="#blacklistForm">Blacklist Client</button>
            @endif
            <a href="{{ route('clients.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
        </div>
    </div>

    <div class="collapse no-print mb-3" id="convertTypeForm">
        <div class="card card-body shadow-sm">
            @if($client->client_type === 'business')
                <form action="{{ route('clients.convert-type', $client) }}" method="POST" onsubmit="return confirm('Convert {{ $client->name }} to an Individual account?');">
                    @csrf
                    <input type="hidden" name="client_type" value="individual">
                    <p class="small mb-2">Convert this client back to an <strong>Individual</strong> account. Business details are kept on file but hidden.</p>
                    <button type="submit" class="btn btn-secondary btn-sm">Confirm Conversion</button>
                </form>
            @else
                <form action="{{ route('clients.convert-type', $client) }}" method="POST" onsubmit="return confirm('Convert {{ $client->name }} to a Business account?');">
                    @csrf
                    <input type="hidden" name="client_type" value="business">
                    <label class="small fw-bold">Business Name</label>
                    <input type="text" name="business_name" class="form-control form-control-sm mb-2" value="{{ old('business_name', $client->business_name) }}" required>
                    <label class="small fw-bold">Registration Number <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" name="business_registration_number" class="form-control form-control-sm mb-2" value="{{ old('business_registration_number', $client->business_registration_number) }}">
                    <button type="submit" class="btn btn-secondary btn-sm">Confirm Conversion</button>
                </form>
            @endif
        </div>
    </div>

    <div class="collapse no-print mb-3" id="transferGroupForm">
        <div class="card card-body shadow-sm">
            <form action="{{ route('clients.transfer-group', $client) }}" method="POST" onsubmit="return confirm('Move {{ $client->name }} to the selected group?');">
                @csrf
                <label class="small fw-bold">Client Group</label>
                <div class="d-flex" style="gap: 0.5rem;">
                    <select name="client_group_id" class="form-control form-control-sm">
                        <option value="">-- No Group --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected($client->groups->contains('id', $group->id))>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
                <small class="text-muted d-block mt-1">Existing loans keep the group they were disbursed under — this only changes membership going forward.</small>
            </form>
        </div>
    </div>

    @if($client->is_blacklisted)
        <div class="alert alert-dark shadow-sm no-print">
            Blacklisted {{ optional($client->blacklisted_at)->format('d M Y') }} by {{ optional($client->blacklistedBy)->name ?? 'a manager' }}.
            @if($client->blacklist_reason) Reason: {{ $client->blacklist_reason }} @endif
            New loan applications are blocked while blacklisted.
        </div>
    @else
        <div class="collapse no-print mb-3" id="blacklistForm">
            <div class="card card-body shadow-sm">
                <form action="{{ route('clients.blacklist', $client) }}" method="POST" onsubmit="return confirm('Blacklist {{ $client->name }}? They will not be able to receive new loans until removed from the blacklist.');">
                    @csrf
                    <label class="small fw-bold">Reason <span class="text-muted fw-normal">(Optional)</span></label>
                    <textarea name="blacklist_reason" class="form-control form-control-sm mb-2" rows="2"></textarea>
                    <button type="submit" class="btn btn-dark btn-sm">Confirm Blacklist</button>
                </form>
            </div>
        </div>
    @endif

    <div class="row">

        {{-- Left Column: Client Information --}}
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Client Details</h6>
                    <span class="badge badge-success">Active</span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img class="img-profile rounded-circle mb-2"
                             src="{{ $client->photo_path ? route('clients.photo', $client) : 'https://ui-avatars.com/api/?name=' . urlencode($client->name) . '&background=4e73df&color=ffffff' }}"
                             style="width: 100px; height: 100px; object-fit: cover;">
                        <h4 class="h5 font-weight-bold text-gray-800">{{ $client->name }}</h4>
                        <p class="text-muted mb-0">ID: #{{ $client->id }}</p>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="font-weight-bold text-secondary text-uppercase text-xs">Contact Info</h6>
                        <div class="pl-2">
                            <p class="mb-1"><strong>Phone:</strong> <a href="tel:{{ $client->phone_number }}">{{ $client->phone_number }}</a></p>
                            <p class="mb-1"><strong>Email:</strong> {{ $client->email ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Address:</strong> {{ $client->address }}</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="font-weight-bold text-secondary text-uppercase text-xs">Personal Info</h6>
                        <div class="pl-2">
                            {{-- FIX: Added 'no-print' class to hide sensitive info on paper --}}
                            <p class="mb-1 no-print"><strong>National ID:</strong> {{ $client->national_id ?? 'N/A' }}</p>

                            {{-- FIX: Using business_occupation instead of occupation --}}
                            <p class="mb-1"><strong>Occupation:</strong> {{ $client->business_occupation ?? 'N/A' }}</p>

                            {{-- FIX: Added 'no-print' class to hide sensitive info on paper --}}
                            <p class="mb-1 no-print"><strong>Date of Birth:</strong> {{ $client->date_of_birth ?? 'N/A' }}</p>
                            <p class="mb-1 no-print"><strong>Gender:</strong> {{ $client->gender ?? 'N/A' }}</p>
                            <p class="mb-1 no-print">
                                <strong>ID Document:</strong>
                                @if($client->id_document_path)
                                    <a href="{{ route('clients.id-document', $client) }}" target="_blank">View</a>
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mb-3 no-print">
                        <h6 class="font-weight-bold text-secondary text-uppercase text-xs">Account Settings</h6>
                        <div class="pl-2">
                            <p class="mb-1"><strong>Assigned Officer:</strong> {{ optional($client->assignedUser)->name ?? 'Unassigned' }}</p>
                            <p class="mb-1"><strong>Notifications:</strong> {{ ucfirst($client->preferred_notification_channel ?? 'sms') }}</p>
                        </div>
                    </div>

                    @if($client->next_of_kin_name || $client->next_of_kin_phone)
                        <div class="mb-3 no-print">
                            <h6 class="font-weight-bold text-secondary text-uppercase text-xs">Next of Kin</h6>
                            <div class="pl-2">
                                <p class="mb-1"><strong>Name:</strong> {{ $client->next_of_kin_name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $client->next_of_kin_phone ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Relationship:</strong> {{ $client->next_of_kin_relationship ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif

                    <hr class="no-print">

                    <div class="d-flex justify-content-center no-print">
                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary btn-icon-split btn-sm mr-2">
                            <span class="icon text-white-50"><i class="fas fa-edit"></i></span>
                            <span class="text">Edit Profile</span>
                        </a>
                        <a href="{{ route('clients.ledger', $client) }}" class="btn btn-info btn-icon-split btn-sm mr-2">
                            <span class="icon text-white-50"><i class="fas fa-book"></i></span>
                            <span class="text">View Ledger</span>
                        </a>
                        <a href="{{ route('clients.statement', $client) }}" target="_blank" class="btn btn-secondary btn-icon-split btn-sm">
                            <span class="icon text-white-50"><i class="fas fa-print"></i></span>
                            <span class="text">Print Statement</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Loan History --}}
        <div class="col-lg-7">
            
            {{-- Stats Cards Row --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Active Loans</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $client->loans->where('status', '!=', 'paid')->count() }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Paid Loans</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $client->loans->where('status', 'paid')->count() }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Loan List --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-gray-800">Loan History</h6>
                    <a href="{{ route('loans.create', ['client_id' => $client->id]) }}" class="btn btn-sm btn-success shadow-sm no-print">
                        <i class="fas fa-plus fa-sm text-white-50"></i> New Loan
                    </a>
                </div>
                <div class="card-body">
                    @if($client->loans->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="loansTable" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>Principal</th>
                                        <th>Date Given</th>
                                        <th>Status</th>
                                        <th class="no-print">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($client->loans as $loan)
                                    <tr>
                                        <td>
                                            <a href="{{ route('loans.show', $loan->id) }}" class="font-weight-bold text-primary">
                                                #{{ $loan->id }}
                                            </a>
                                        </td>
                                        <td>{{ number_format($loan->principal_amount) }}</td>
                                        <td>{{ $loan->created_at->format('d M Y') }}</td>
                                        <td>
                                            @if($loan->status === 'paid')
                                                <span class="badge badge-success px-2 py-1">Paid</span>
                                            @elseif($loan->status === 'approved' || $loan->status === 'active')
                                                <span class="badge badge-info px-2 py-1">Active</span>
                                            @else
                                                <span class="badge badge-warning px-2 py-1">{{ ucfirst($loan->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="no-print">
                                            <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-info btn-circle btn-sm" title="View Loan">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">No loans found for this client.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .navbar, .sidebar { display: none !important; }
    }
</style>
@endsection