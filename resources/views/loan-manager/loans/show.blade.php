@extends('layouts.manager')

@section('title', 'Loan Details')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">
                Loan #{{ $loan->reference_id ?? str_pad($loan->id, 4, '0', STR_PAD_LEFT) }}
                @if($loan->status == 'paid')
                    <span class="badge bg-success" style="font-size: 0.5em; vertical-align: middle;">PAID</span>
                @elseif($loan->status == 'defaulted')
                    <span class="badge bg-danger" style="font-size: 0.5em; vertical-align: middle;">DEFAULTED</span>
                @else
                    <span class="badge bg-primary" style="font-size: 0.5em; vertical-align: middle;">ACTIVE</span>
                @endif
            </h1>
            <p class="mb-0 text-muted">
                Client: <strong>{{ $loan->client->name }}</strong> | Phone: {{ $loan->client->phone_number }}
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
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
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
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                    <i class="fas fa-plus"></i> Add Payment
                                </button>
                            </div>
                            
                            @if($loan->payments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt #</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($loan->payments->sortByDesc('payment_date') as $payment)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
                                                <td>{{ $payment->reference_id ?? $payment->receipt_number ?? '-' }}</td>
                                                <td class="text-success fw-bold">{{ number_format($payment->amount_paid) }}</td>
                                                <td>{{ ucfirst($payment->payment_method) }}</td>
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
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">Record First Payment</button>
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

{{-- Add Payment Modal (BS5 Syntax) --}}
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                
                <div class="modal-header">
                    <h5 class="modal-title">Record New Payment</h5>
                    {{-- BS5 Close Button --}}
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount ({{ $currency }})</label>
                        <input type="number" name="amount_paid" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash" selected>Cash</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Mobile Money">Mobile Money</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection