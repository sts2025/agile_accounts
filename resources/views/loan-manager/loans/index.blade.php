@extends('layouts.manager')

@section('title', 'My Loans')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Loans</h1>
        <div>
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">Manage Clients</a>
            <a href="{{ route('loans.create') }}" class="btn btn-primary">Create New Loan</a>
            {{-- NEW PENDING FILTER BUTTON --}}
            <a href="{{ route('loans.index', ['filter' => 'pending']) }}" class="btn btn-warning ms-2 fw-bold text-dark">
                <i class="fas fa-clock"></i> Pending Approvals
            </a>
        </div>
    </div>
    
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif
    
    <div id="status-message-container"></div>

    <div class="card mb-4">
        <div class="card-header">Find a Loan</div>
        <div class="card-body">
            <form method="GET" action="{{ route('loans.index') }}">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by client's name..." value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Principal</th>
                            <th>Interest</th>
                            <th>Total Due</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                            @php
                                // --- CALCULATE MISSING INFO ---
                                $currency = $currency_symbol ?? 'UGX';
                                
                                // 1. Interest Amount
                                $interest = $loan->principal_amount * ($loan->interest_rate / 100);
                                
                                // 2. Total Due
                                $totalDue = $loan->principal_amount + $interest;
                                
                                // 3. Paid (Sum of payments)
                                $paid = $loan->payments->sum('amount_paid');
                                
                                // 4. Balance
                                $balance = $totalDue - $paid;
                                if($balance < 0) $balance = 0;
                            @endphp
                            <tr>
                                {{-- Client Info --}}
                                <td>
                                    <a href="{{ route('clients.edit', $loan->client->id) }}" class="font-weight-bold text-dark" style="text-decoration: underline;">
                                        {{ $loan->client->name ?? 'Unknown' }}
                                    </a>
                                    
                                    {{-- NEW: Loan Number Badge --}}
                                    <span class="badge badge-light border text-dark" style="font-size: 0.75rem; margin-left: 8px;">
                                        Loan #{{ $loan->id }}
                                    </span>

                                    @if($loan->clientGroup)
                                        <span class="badge bg-info text-dark" style="font-size: 0.75rem; margin-left: 4px;" title="Group Loan">
                                            <i class="fas fa-users"></i> {{ $loan->clientGroup->name }}
                                        </span>
                                    @endif

                                    <br>
                                    <small class="text-muted">{{ $loan->client->phone_number ?? '' }}</small>
                                </td>

                                {{-- Principal --}}
                                <td>{{ number_format($loan->principal_amount) }}</td>

                                <td>
                                    {{ number_format($interest) }}
                                    <small class="d-block text-muted">{{ $loan->interest_rate }}%</small>
                                </td>

                                <td class="font-weight-bold">{{ number_format($totalDue) }}</td>
                                <td class="text-success">{{ number_format($paid) }}</td>
                                <td class="text-danger font-weight-bold">
                                    {{ number_format($balance) }} <small>{{ $currency }}</small>
                                </td>

                                {{-- Status column: three pre-disbursement states get a plain badge
                                     (nothing to toggle yet); only a disbursed loan gets the
                                     active<->paid toggle button. --}}
                                <td>
                                    @if($loan->approval_status === 'pending')
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Pending Approval</span>
                                    @elseif($loan->approval_status === 'approved')
                                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">Approved</span>
                                    @elseif($loan->approval_status === 'rejected')
                                        <span class="badge bg-dark px-3 py-2 rounded-pill">Rejected</span>
                                    @elseif($loan->status === 'written_off')
                                        <span class="badge bg-dark px-3 py-2 rounded-pill"><i class="fas fa-file-invoice-dollar me-1"></i> Written Off</span>
                                    @else
                                        <form action="{{ route('loans.update-status', $loan->id) }}" method="POST" class="d-inline status-form" data-loan-id="{{ $loan->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="new_status" value="{{ $loan->status == 'active' ? 'paid' : 'active' }}">

                                            @php
                                                $btnClass = match($loan->status) {
                                                    'active' => 'btn-primary',
                                                    'paid' => 'btn-success',
                                                    'defaulted' => 'btn-danger',
                                                    'rejected' => 'btn-dark',
                                                    default => 'btn-secondary'
                                                };
                                            @endphp

                                            <button type="submit"
                                                    class="btn btn-sm text-white rounded-pill loan-status-btn {{ $btnClass }}"
                                                    data-current-status="{{ $loan->status }}"
                                                    id="status-btn-{{ $loan->id }}"
                                                    style="min-width: 80px;">
                                                {{ ucfirst($loan->status) }}
                                            </button>
                                        </form>
                                    @endif
                                </td>

                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($loan->approval_status === 'pending')
                                            <form method="POST" action="{{ route('loans.approve', $loan->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this loan application? It will still need to be disbursed separately.');" title="Approve"><i class="fas fa-check"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('loans.reject', $loan->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this application?');" title="Reject"><i class="fas fa-times"></i></button>
                                            </form>
                                        @elseif($loan->approval_status === 'approved')
                                            <form method="POST" action="{{ route('loans.disburse', $loan->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-primary" onclick="return confirm('Disburse this loan? This marks it active and releases the funds.');" title="Disburse"><i class="fas fa-hand-holding-usd"></i></button>
                                            </form>
                                        @endif

                                        <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-info text-white" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="{{ route('loans.edit', $loan->id) }}" class="btn btn-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No loans found. <a href="{{ route('loans.create') }}">Create one now</a>.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <div class="mt-3">
                    {{ $loans->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const messageContainer = document.getElementById('status-message-container');

        function displayMessage(message, type = 'success') {
            messageContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            setTimeout(() => { messageContainer.innerHTML = ''; }, 5000);
        }

        document.querySelectorAll('.status-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const loanId = this.dataset.loanId;
                const button = document.getElementById(`status-btn-${loanId}`);
                const currentStatus = button.dataset.currentStatus;
                const newStatusInput = this.querySelector('input[name="new_status"]');
                const newStatus = newStatusInput.value;
                
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                if (!confirm(`Change status from '${currentStatus.toUpperCase()}' to '${newStatus.toUpperCase()}'?`)) {
                    return;
                }

                const originalText = button.textContent;
                button.textContent = '...';
                button.disabled = true;

                fetch(this.action, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ new_status: newStatus })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json()
                            .catch(() => ({}))
                            .then(data => { throw new Error(data.message || 'Failed to update'); });
                    }
                    return response.json();
                })
                .then(data => {
                    const updatedStatus = data.status;
                    let buttonClass = 'btn-secondary';

                    if (updatedStatus === 'paid') {
                        buttonClass = 'btn-success';
                        newStatusInput.value = 'active';
                    } else if (updatedStatus === 'active') {
                        buttonClass = 'btn-primary';
                        newStatusInput.value = 'paid';
                    } else if (updatedStatus === 'defaulted') {
                        buttonClass = 'btn-danger';
                    }
                    
                    button.className = `btn btn-sm text-white rounded-pill loan-status-btn ${buttonClass}`;
                    button.textContent = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    button.dataset.currentStatus = updatedStatus;
                    button.disabled = false;

                    displayMessage(`Loan status updated to ${updatedStatus.toUpperCase()}`, 'success');
                })
                .catch(error => {
                    console.error(error);
                    button.textContent = originalText;
                    button.disabled = false;
                    displayMessage(error.message || 'Error updating status.', 'danger');
                });
            });
        });
    });
</script>
@endsection