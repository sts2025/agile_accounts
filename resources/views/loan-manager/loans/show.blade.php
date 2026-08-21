@extends('layouts.manager')

@section('title', 'Loan Details')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">
                Loan #{{ $loan->reference_id ?? str_pad($loan->id, 4, '0', STR_PAD_LEFT) }}
                @if($loan->approval_status === 'pending')
                    <span class="badge bg-warning text-dark" style="font-size: 0.5em; vertical-align: middle;">PENDING APPROVAL</span>
                @elseif($loan->approval_status === 'approved')
                    <span class="badge bg-info text-dark" style="font-size: 0.5em; vertical-align: middle;">APPROVED — AWAITING DISBURSEMENT</span>
                @elseif($loan->approval_status === 'rejected')
                    <span class="badge bg-dark" style="font-size: 0.5em; vertical-align: middle;">REJECTED</span>
                @elseif($loan->status == 'paid')
                    <span class="badge bg-success" style="font-size: 0.5em; vertical-align: middle;">PAID</span>
                @elseif($loan->status == 'defaulted')
                    <span class="badge bg-danger" style="font-size: 0.5em; vertical-align: middle;">DEFAULTED</span>
                @else
                    <span class="badge bg-primary" style="font-size: 0.5em; vertical-align: middle;">ACTIVE</span>
                @endif
            </h1>
            <p class="mb-0 text-muted">
                Client: <strong>{{ $loan->client->name }}</strong> | Phone: {{ $loan->client->phone_number }}
                @if($loan->clientGroup)
                    | Group: <a href="{{ route('client-groups.show', $loan->clientGroup->id) }}"><i class="fas fa-users"></i> {{ $loan->clientGroup->name }}</a>
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('loans.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            {{-- Agreement Button --}}
            <a href="{{ route('loans.downloadAgreement', $loan->id) }}" class="btn btn-dark shadow-sm ms-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Agreement
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Loan Workflow: Application -> Approval -> Disbursement --}}
    @if(in_array($loan->approval_status, ['pending', 'approved']) || ($loan->approval_status === 'disbursed' && !$loan->payments->count()))
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-1 fw-bold">Loan Workflow</h6>
                    <span class="text-muted small">
                        @if($loan->approval_status === 'pending')
                            Awaiting approval.
                        @elseif($loan->approval_status === 'approved')
                            Approved by {{ optional($loan->approvedBy)->name ?? 'manager' }} on {{ optional($loan->approved_at)->format('d M Y') }} — not yet disbursed.
                        @elseif($loan->approval_status === 'disbursed')
                            Disbursed — no repayments recorded yet, so this can still be reversed if it was a mistake.
                        @endif
                    </span>
                </div>
                <div class="d-flex gap-2">
                    @if($loan->approval_status === 'pending')
                        <form method="POST" action="{{ route('loans.approve', $loan->id) }}" onsubmit="return confirm('Approve this loan application?');">
                            @csrf
                            <button type="submit" class="btn btn-success shadow-sm"><i class="fas fa-check me-1"></i> Approve</button>
                        </form>
                        <form method="POST" action="{{ route('loans.reject', $loan->id) }}" onsubmit="return confirm('Reject this application?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger shadow-sm"><i class="fas fa-times me-1"></i> Reject</button>
                        </form>
                    @elseif($loan->approval_status === 'approved')
                        <form method="POST" action="{{ route('loans.disburse', $loan->id) }}" onsubmit="return confirm('Disburse this loan? This marks it active and releases the funds.');">
                            @csrf
                            <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-hand-holding-usd me-1"></i> Disburse</button>
                        </form>
                    @elseif($loan->approval_status === 'disbursed')
                        <form method="POST" action="{{ route('loans.reverse-disbursement', $loan->id) }}" onsubmit="return confirm('Reverse this disbursement? The loan goes back to Approved, awaiting disbursement.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning shadow-sm"><i class="fas fa-undo me-1"></i> Reverse Disbursement</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($loan->status === 'written_off')
        <div class="alert alert-secondary shadow-sm border-0 mb-4">
            <strong><i class="fas fa-file-invoice-dollar me-2"></i> Written off</strong>
            by {{ optional($loan->writtenOffBy)->name ?? 'a manager' }} on {{ optional($loan->written_off_at)->format('d M Y') }}.
            @if($loan->write_off_reason)
                <div class="small text-muted mt-1">Reason: {{ $loan->write_off_reason }}</div>
            @endif
        </div>
    @elseif($loan->approval_status === 'disbursed' && !in_array($loan->status, ['paid', 'written_off']))
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-secondary btn-sm shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#rescheduleForm">
                    <i class="fas fa-calendar-alt me-1"></i> Reschedule / Refinance
                </button>
                <button class="btn btn-outline-dark btn-sm shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#writeOffForm">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Write Off This Loan
                </button>

                <div class="collapse w-100 mt-3" id="rescheduleForm">
                    <form method="POST" action="{{ route('loans.reschedule', $loan->id) }}" onsubmit="return confirm('Reschedule this loan with the new terms below? The current terms will be kept on record.');" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Interest Rate (%)</label>
                            <input type="number" step="0.01" name="interest_rate" class="form-control form-control-sm" value="{{ $loan->interest_rate }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Term (Periods)</label>
                            <input type="number" name="term" class="form-control form-control-sm" value="{{ $loan->term }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Frequency</label>
                            <select name="repayment_frequency" class="form-select form-select-sm" required>
                                @foreach(['Monthly', 'Weekly', 'Daily'] as $freq)
                                    <option value="{{ $freq }}" {{ $loan->repayment_frequency === $freq ? 'selected' : '' }}>{{ $freq }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">New Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ \Carbon\Carbon::parse($loan->start_date)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Reason <span class="text-secondary fw-normal">(Optional)</span></label>
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. client requested extended term after income disruption">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-secondary btn-sm">Confirm Reschedule</button>
                        </div>
                    </form>
                </div>

                <div class="collapse w-100 mt-3" id="writeOffForm">
                    <form method="POST" action="{{ route('loans.write-off', $loan->id) }}" onsubmit="return confirm('Write off this loan as uncollectable bad debt? This is meant to be final — there is no undo action.');">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Reason <span class="text-secondary fw-normal">(Optional)</span></label>
                            <textarea name="write_off_reason" class="form-control form-control-sm" rows="2" placeholder="e.g. client deceased, absconded, uncollectable after X months in arrears"></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">Confirm Write Off</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if(in_array($loan->status, ['active', 'defaulted'], true) || $loan->penalties->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-warning"><i class="fas fa-exclamation-triangle me-2"></i> Penalties</h6>
                @if(in_array($loan->status, ['active', 'defaulted'], true))
                    <button class="btn btn-sm btn-outline-warning shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addPenaltyForm">
                        <i class="fas fa-plus me-1"></i> Add Penalty
                    </button>
                @endif
            </div>
            <div class="card-body">
                @if(in_array($loan->status, ['active', 'defaulted'], true))
                    <div class="collapse mb-3" id="addPenaltyForm">
                        <form method="POST" action="{{ route('loans.penalties.store', $loan->id) }}" class="row g-2">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control form-control-sm" value="{{ $defaultPenaltyAmount > 0 ? $defaultPenaltyAmount : '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Reason <span class="text-secondary fw-normal">(Optional)</span></label>
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. 15 days late on installment">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-warning btn-sm w-100">Add</button>
                            </div>
                        </form>
                    </div>
                @endif

                @if($loan->penalties->isEmpty())
                    <p class="text-muted small mb-0">No penalties on this loan.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="text-secondary small text-uppercase">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loan->penalties as $penalty)
                                    <tr>
                                        <td class="small">{{ $penalty->created_at->format('d M Y') }}</td>
                                        <td class="small font-monospace {{ $penalty->is_removed ? 'text-muted text-decoration-line-through' : '' }}">{{ number_format($penalty->amount, 2) }}</td>
                                        <td class="small text-muted">{{ $penalty->reason ?: '—' }}</td>
                                        <td class="small">
                                            @if($penalty->is_removed)
                                                <span class="badge bg-light text-muted border">Removed</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Active</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$penalty->is_removed)
                                                <form method="POST" action="{{ route('loans.penalties.destroy', [$loan->id, $penalty->id]) }}" onsubmit="return confirm('Remove (waive) this penalty?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Remove</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($loan->reschedules->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-secondary"><i class="fas fa-history me-2"></i> Reschedule History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>By</th>
                                <th>Old Terms</th>
                                <th>New Terms</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loan->reschedules as $r)
                                <tr>
                                    <td class="ps-4 small">{{ $r->created_at->format('d M Y') }}</td>
                                    <td class="small">{{ optional($r->rescheduledBy)->name ?? '—' }}</td>
                                    <td class="small text-muted">{{ $r->old_interest_rate }}% / {{ $r->old_term }} {{ $r->old_repayment_frequency }} / from {{ $r->old_start_date->format('d M Y') }}</td>
                                    <td class="small">{{ $r->new_interest_rate }}% / {{ $r->new_term }} {{ $r->new_repayment_frequency }} / from {{ $r->new_start_date->format('d M Y') }}</td>
                                    <td class="small text-muted">{{ $r->reason ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ADDED: Display hidden Validation or Database Errors --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <strong><i class="fas fa-exclamation-circle me-2"></i> Payment Failed to Save!</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        
        {{-- LEFT COLUMN: Loan Summary --}}
        <div class="col-lg-4 mb-4">
            
            {{-- Client Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Client Profile</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-secondary"></i>
                    </div>
                    <h5 class="font-weight-bold">{{ $loan->client->name }}</h5>
                    <p class="text-muted mb-2">{{ $loan->client->business_occupation ?? 'Occupation N/A' }}</p>
                    <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt me-1"></i> {{ $loan->client->address }}</p>
                    <hr>
                    <a href="{{ route('clients.edit', $loan->client->id) }}" class="btn btn-sm btn-outline-primary w-100">View Full Profile</a>
                </div>
            </div>

            {{-- Financial Summary --}}
            <div class="card shadow-sm mb-4 border-start border-primary border-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Financial Summary</h6>
                </div>
                <div class="card-body">
                    @php
                        $manager = Auth::user()->loanManager;
                        $currency = $manager->currency_symbol ?? 'UGX';
                        
                        $principal = $loan->principal_amount;
                        // Calculate interest based on rate
                        $calculatedInterest = $principal * ($loan->interest_rate / 100);
                        // Use stored interest_amount if available, otherwise calculated
                        $interest = $loan->interest_amount ?? $calculatedInterest;
                        
                        $totalDue = $principal + $interest;
                        $totalPaid = $loan->payments->sum('amount_paid');
                        $balance = max(0, $totalDue - $totalPaid);
                        
                        $progress = ($totalDue > 0) ? ($totalPaid / $totalDue) * 100 : 0;
                    @endphp

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-muted fw-bold">Principal</span>
                        <span class="fw-bold">{{ number_format($principal) }} {{ $currency }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-muted fw-bold">Interest ({{ $loan->interest_rate }}%)</span>
                        <span class="fw-bold">{{ number_format($interest) }} {{ $currency }}</span>
                    </div>
                    
                    <hr class="my-2">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-dark fw-bold">Total Due</span>
                        <span class="fw-bold text-dark">{{ number_format($totalDue) }} {{ $currency }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-uppercase text-success fw-bold">Paid</span>
                        <span class="fw-bold text-success">{{ number_format($totalPaid) }} {{ $currency }}</span>
                    </div>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                    </div>

                    <div class="p-3 bg-light rounded border border-danger">
                        <div class="small text-danger text-uppercase fw-bold mb-1">Balance Due</div>
                        <div class="h4 mb-0 font-weight-bold text-danger">{{ number_format($balance) }} {{ $currency }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Tabs for Details --}}
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header border-bottom-0">
                    {{-- BOOTSTRAP 5 TABS --}}
                    <ul class="nav nav-tabs card-header-tabs" id="loanTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab">
                                <i class="fas fa-calendar-alt me-2"></i> Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                                <i class="fas fa-history me-2"></i> History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="guarantors-tab" data-bs-toggle="tab" data-bs-target="#guarantors" type="button" role="tab">
                                <i class="fas fa-users me-2"></i> Guarantors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="collateral-tab" data-bs-toggle="tab" data-bs-target="#collateral" type="button" role="tab">
                                <i class="fas fa-car me-2"></i> Collateral
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="loanTabsContent">
                        
                        {{-- TAB 1: SCHEDULE --}}
                        <div class="tab-pane fade show active" id="schedule" role="tabpanel">
                            <h6 class="font-weight-bold text-primary mb-3">Repayment Schedule</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Due Date</th>
                                            <th class="text-end">Installment</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($schedule) && count($schedule) > 0)
                                            @foreach($schedule as $row)
                                            <tr>
                                                <td>{{ $row['period'] }}</td>
                                                <td>{{ \Carbon\Carbon::parse($row['due_date'])->format('d M, Y') }}</td>
                                                <td class="text-end fw-bold">{{ number_format($row['payment_amount']) }}</td>
                                                <td class="text-end">{{ number_format(max(0, $row['balance'])) }}</td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    Schedule calculation data unavailable.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- TAB 2: PAYMENT HISTORY --}}
                        <div class="tab-pane fade" id="payments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-success m-0">Payment History</h6>
                                {{-- Trigger Modal (BS5 Syntax) --}}
                                <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                    <i class="fas fa-plus me-1"></i> Record Payment
                                </button>
                            </div>
                            
                            @if($loan->payments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt #</th>
                                                <th class="text-end">Principal</th>
                                                <th class="text-end">Interest</th>
                                                <th class="text-end">Total Amount</th>
                                                <th>Method</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($loan->payments->sortByDesc('payment_date') as $payment)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
                                                <td class="text-muted small">{{ $payment->reference_id ?? $payment->receipt_number ?? '-' }}</td>
                                                <td class="text-end text-primary font-monospace">{{ number_format($payment->principal_paid) }}</td>
                                                <td class="text-end text-warning font-monospace">{{ number_format($payment->interest_paid) }}</td>
                                                <td class="text-end text-success fw-bold bg-light font-monospace">{{ number_format($payment->amount_paid) }}</td>
                                                <td><span class="badge bg-secondary">{{ ucfirst($payment->payment_method) }}</span></td>
                                                <td>
                                                    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-sm btn-info text-white" target="_blank" title="Print Receipt">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    {{-- Only allow editing if not admin --}}
                                                    <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5 bg-light rounded">
                                    <i class="fas fa-receipt fa-3x text-secondary mb-3"></i>
                                    <p class="text-muted">No payments recorded yet.</p>
                                    <button class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">Record First Payment</button>
                                </div>
                            @endif
                        </div>

                        {{-- TAB 3: GUARANTORS --}}
                        <div class="tab-pane fade" id="guarantors" role="tabpanel">
                            @if($loan->guarantor_name || ($loan->guarantors && $loan->guarantors->count() > 0))
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Phone</th>
                                                <th>Relationship</th>
                                                <th>Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($loan->guarantors && $loan->guarantors->count() > 0)
                                                @foreach($loan->guarantors as $g)
                                                <tr>
                                                    <td class="fw-bold">{{ $g->first_name }} {{ $g->last_name }}</td>
                                                    <td>{{ $g->phone_number }}</td>
                                                    <td>{{ $g->relationship_to_borrower }}</td>
                                                    <td>{{ $g->address }}</td>
                                                </tr>
                                                @endforeach
                                            @elseif($loan->guarantor_name)
                                                {{-- Fallback for simple fields --}}
                                                <tr>
                                                    <td class="fw-bold">{{ $loan->guarantor_name }}</td>
                                                    <td>{{ $loan->guarantor_phone }}</td>
                                                    <td>{{ $loan->guarantor_relationship }}</td>
                                                    <td>{{ $loan->guarantor_address }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">No guarantor details recorded.</div>
                            @endif
                        </div>

                        {{-- TAB 4: COLLATERAL --}}
                        <div class="tab-pane fade" id="collateral" role="tabpanel">
                            @if($loan->collateral_name || ($loan->collaterals && $loan->collaterals->count() > 0))
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Value</th>
                                                <th>Description/Condition</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($loan->collaterals && $loan->collaterals->count() > 0)
                                                @foreach($loan->collaterals as $c)
                                                <tr>
                                                    <td class="fw-bold">{{ $c->name }} {{ $c->collateral_type }}</td>
                                                    <td>{{ number_format($c->valuation_amount ?? $c->value) }}</td>
                                                    <td>{{ $c->description }} ({{ $c->condition }})</td>
                                                </tr>
                                                @endforeach
                                            @elseif($loan->collateral_name)
                                                <tr>
                                                    <td class="fw-bold">{{ $loan->collateral_name }}</td>
                                                    <td>{{ number_format((float)$loan->collateral_value) }}</td>
                                                    <td>{{ $loan->collateral_description }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">No collateral recorded.</div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- UPDATED Add Payment Modal with Split Logic (BS5 Syntax) --}}
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd me-2"></i>Record New Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body bg-light">
                    {{-- NEW: Split Payment Breakdown --}}
                    <div class="card p-3 border-success bg-white shadow-sm mb-3">
                        <h6 class="text-success fw-bold mb-3 border-bottom pb-2">Payment Breakdown</h6>
                        
                        <div class="row mb-2">
                            <div class="col-7"><label class="mb-0 fw-bold">Principal Paid:</label></div>
                            <div class="col-5">
                                <input type="number" name="principal_paid" id="detailModalPrincipal" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calcDetailModalTotal()">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-7"><label class="mb-0 fw-bold">Interest Paid:</label></div>
                            <div class="col-5">
                                <input type="number" name="interest_paid" id="detailModalInterest" class="form-control text-end fw-bold" placeholder="0" min="0" required oninput="calcDetailModalTotal()">
                            </div>
                        </div>

                        <div class="row mt-3 pt-2 border-top">
                            <div class="col-7"><label class="mb-0 fw-bold text-uppercase">Total Amount:</label></div>
                            <div class="col-5 text-end"><h5 class="mb-0 fw-bold text-success" id="detailModalTotal">0.00</h5></div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control shadow-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Method</label>
                            <select name="payment_method" class="form-select shadow-sm">
                                <option value="Cash" selected>Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Money">Mobile Money</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Reference / Notes</label>
                        <input type="text" name="notes" class="form-control shadow-sm" placeholder="Optional details">
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live calculation logic for the Split Payment Modal on the Loan Details page
    function calcDetailModalTotal() {
        let p = parseFloat(document.getElementById('detailModalPrincipal').value) || 0;
        let i = parseFloat(document.getElementById('detailModalInterest').value) || 0;
        let display = document.getElementById('detailModalTotal');
        if(display) {
            display.innerText = (p + i).toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    }
</script>
@endpush

@endsection