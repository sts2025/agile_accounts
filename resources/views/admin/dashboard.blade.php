@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('page_heading')
    Admin Dashboard <span class="badge badge-success shadow-sm" style="font-size: 0.5em; vertical-align: middle;">SEARCH ENABLED</span>
@endsection

@section('content')
    
    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success border-left-success shadow mb-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-info border-left-info shadow mb-4">
            <i class="fas fa-info-circle mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-left-danger shadow mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Main Table --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Registered Loan Managers</h6>
            
            <div class="d-flex align-items-center">
                {{-- SEARCH BAR ADDED HERE --}}
                <div class="input-group input-group-sm mr-3 shadow-sm" style="width: 250px;">
                    <input type="text" id="managerSearch" class="form-control bg-light border-0 small" placeholder="Search manager..." aria-label="Search">
                    <div class="input-group-append">
                        <span class="input-group-text bg-primary border-primary text-white">
                            <i class="fas fa-search fa-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="dropdown no-arrow">
                    <a href="{{ route('admin.broadcasts.index') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-bullhorn fa-sm text-white-50"></i> Broadcast
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Manager Info</th>
                            <th>Company</th>
                            <th class="text-center">Status</th>
                            <th>Subscription Expiry</th>
                            <th class="text-center" style="min-width: 250px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($managers as $manager)
                            @php
                                $profile = $manager->loanManager;
                                $isActive = $profile && !empty($profile->currency_symbol);
                                
                                $expiry = ($profile && $profile->subscription_expires_at) ? \Carbon\Carbon::parse($profile->subscription_expires_at) : null;
                                $isExpired = $expiry ? $expiry->isPast() : false;
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $manager->name }}</div>
                                    <div class="small text-muted">{{ $manager->email }}</div>
                                </td>
                                <td>
                                    @if($profile && $profile->company_name)
                                        <div class="text-primary font-weight-bold">{{ $profile->company_name }}</div>
                                        <div class="small text-muted"><i class="fas fa-phone-alt mr-1"></i>{{ $profile->support_phone ?? 'No Phone' }}</div>
                                    @else
                                        <span class="text-muted font-italic small">No Profile Set</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if ($isActive && $profile->is_active)
                                        <span class="badge badge-success px-3 py-1 shadow-sm">Active</span>
                                    @else
                                        <span class="badge badge-secondary px-3 py-1 shadow-sm">Inactive</span>
                                    @endif
                                </td>
                                
                                <td class="align-middle">
                                    @if($expiry)
                                        <div class="{{ $isExpired ? 'text-danger font-weight-bold' : 'text-success font-weight-bold' }}">
                                            <i class="fas fa-calendar-alt mr-1"></i> {{ $expiry->format('d M, Y') }}
                                        </div>
                                        @if($isExpired)
                                            <span class="badge badge-danger">Expired</span>
                                        @endif
                                    @else
                                        <span class="text-muted small font-italic">Lifetime / Not Set</span>
                                    @endif
                                </td>
                                
                                <td class="text-center align-middle">
                                    <div class="btn-group">
                                        {{-- 1. SUBSCRIPTION BUTTON --}}
                                        @if($profile)
                                            <button type="button" 
                                                class="btn btn-primary btn-sm shadow-sm" 
                                                title="Manage Subscription"
                                                onclick="openSubModal(this)"
                                                data-id="{{ $profile->id }}"
                                                data-name="{{ $manager->name }}"
                                                data-expiry="{{ $expiry ? $expiry->format('d M Y') : 'None' }}">
                                                <i class="fas fa-clock mr-1"></i> Sub
                                            </button>
                                        @endif

                                        {{-- 2. LOGIN AS --}}
                                        <a href="{{ route('admin.users.impersonate', $manager->id) }}" class="btn btn-info btn-sm shadow-sm" title="Login as this user">
                                            <i class="fas fa-user-secret"></i>
                                        </a>

                                        {{-- 3. SUSPEND / ACTIVATE --}}
                                        @if ($isActive && $profile->is_active)
                                            <form action="{{ route('admin.managers.suspend', $manager->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm shadow-sm" title="Suspend Access" onclick="return confirm('Suspend this manager?')">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-success btn-sm shadow-sm" type="button" data-toggle="collapse" data-target="#activate{{ $manager->id }}" title="Activate Account">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        @endif

                                        {{-- 4. DELETE --}}
                                        <form action="{{ route('admin.managers.destroy', $profile ? $profile->id : $manager->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm shadow-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>

                                    {{-- Inline Activation Form --}}
                                    @if(!$isActive || !$profile->is_active)
                                        <div class="collapse mt-2 text-left" id="activate{{ $manager->id }}">
                                            <div class="p-3 bg-light border rounded shadow-sm">
                                                <form action="{{ route('admin.managers.activate', $manager->id) }}" method="POST">
                                                    @csrf
                                                    <div class="form-group mb-2">
                                                        <label class="small font-weight-bold">Currency</label>
                                                        <select name="currency_symbol" class="form-control form-control-sm" required>
                                                            <option value="UGX">UGX (Uganda)</option>
                                                            <option value="KES">KES (Kenya)</option>
                                                            <option value="TZS">TZS (Tanzania)</option>
                                                            <option value="RWF">RWF (Rwanda)</option>
                                                            <option value="BIF">BIF (Burundi)</option>
                                                            <option value="SSP">SSP (South Sudan)</option>
                                                            <option value="ETB">ETB (Ethiopia)</option>
                                                            <option value="ZAR">ZAR (South Africa)</option>
                                                            <option value="ZMW">ZMW (Zambia)</option>
                                                            <option value="MWK">MWK (Malawi)</option>
                                                            <option value="NGN">NGN (Nigeria)</option>
                                                            <option value="GHS">GHS (Ghana)</option>
                                                            <option value="USD">USD ($)</option>
                                                            <option value="EUR">EUR (€)</option>
                                                            <option value="GBP">GBP (£)</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group mb-2">
                                                        <label class="small font-weight-bold">Support Line</label>
                                                        <input type="text" name="support_phone" class="form-control form-control-sm" placeholder="Support number" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-success btn-sm btn-block font-weight-bold">Activate Now</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No Loan Managers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SUBSCRIPTION POP-UP MODAL --}}
    <div class="modal fade" id="subModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog shadow-lg" role="document">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-calendar-check mr-2"></i>Manage Subscription</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.subscription.update') }}" method="POST">
                    @csrf
                    <div class="modal-body bg-light">
                        <input type="hidden" name="manager_id" id="modal_manager_id">
                        
                        <div class="card mb-3">
                            <div class="card-body p-3 text-dark">
                                <p class="mb-1 text-muted small uppercase font-weight-bold">Loan Manager</p>
                                <h5 class="font-weight-bold mb-2 text-dark" id="modal_manager_name">...</h5>
                                <div class="badge badge-info shadow-sm">Current Expiry: <span id="modal_current_expiry">None</span></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Subscription Duration</label>
                            <select name="duration" class="form-control shadow-sm" id="duration_select" onchange="checkCustomDate(this.value)" required>
                                <option value="" disabled selected>Select duration...</option>
                                <option value="1_month">1 Month</option>
                                <option value="3_months">3 Months</option>
                                <option value="6_months">6 Months</option>
                                <option value="1_year">1 Year</option>
                                <option value="custom">Custom Date</option>
                                <option value="deactivate" class="text-danger font-weight-bold">Deactivate / Expire Now</option>
                            </select>
                        </div>

                        <div id="custom_date_container" class="form-group d-none">
                            <label class="font-weight-bold text-dark">Pick Expiry Date</label>
                            <input type="date" name="custom_date" class="form-control shadow-sm">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success shadow-sm px-4">Update Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    // --- 1. SEARCH FUNCTIONALITY ---
    $(document).ready(function(){
      $("#managerSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#dataTable tbody tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
    });

    // --- 2. MODAL & DATES ---
    function openSubModal(element) {
        var id = element.getAttribute('data-id');
        var name = element.getAttribute('data-name');
        var expiry = element.getAttribute('data-expiry');

        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        $('#modal_manager_id').val(id);
        $('#modal_manager_name').text(name);
        $('#modal_current_expiry').text(expiry || 'No Date Set');
        $('#duration_select').val('');
        $('#custom_date_container').addClass('d-none');
        
        $('#subModal').modal('show');
    }

    function checkCustomDate(val) {
        if (val === 'custom') {
            document.getElementById('custom_date_container').classList.remove('d-none');
        } else {
            document.getElementById('custom_date_container').classList.add('d-none');
        }
    }
</script>
@endsection