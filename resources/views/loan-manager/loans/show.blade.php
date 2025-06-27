@extends('layouts.app')

@section('title', 'Loan Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Loan Details</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#collateralModal">Add Collateral</button>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#guarantorModal">Add Guarantor</button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">Record a Payment</button>
        </div>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-secondary btn-sm mb-3">Back to Loan List</a>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    <hr>

    {{-- Loan Summary Card --}}
    <div class="card mb-4">
        <div class="card-header">Loan Summary</div>
        <div class="card-body">
            @php
                $totalPaid = $loan->payments->sum('amount_paid');
                $monthlyPayment = $schedule[0]['payment_amount'] ?? 0;
                $totalLoanCost = $monthlyPayment * $loan->term;
                $remainingBalance = $totalLoanCost > 0 ? $totalLoanCost - $totalPaid : $loan->principal_amount - $totalPaid;
                $badgeColor = 'bg-secondary';
                switch ($loan->status) {
                    case 'active': $badgeColor = 'bg-primary'; break;
                    case 'paid': $badgeColor = 'bg-success'; break;
                    case 'defaulted': $badgeColor = 'bg-danger'; break;
                    case 'pending': $badgeColor = 'bg-warning text-dark'; break;
                }
            @endphp
            <p><strong>Client:</strong> {{ $loan->client->name }}</p>
            <p><strong>Principal Amount:</strong> UGX {{ number_format($loan->principal_amount, 2) }}</p>
            <p><strong>Total Paid:</strong> <span class="text-success">UGX {{ number_format($totalPaid, 2) }}</span></p>
            <p><strong>Remaining Balance:</strong> <span class="text-danger">UGX {{ number_format($remainingBalance, 2) }}</span></p>
            <p><strong>Status:</strong> <span class="badge {{ $badgeColor }}">{{ ucfirst($loan->status) }}</span></p>
        </div>
    </div>

    {{-- Collateral Card --}}
    <div class="card mb-4">
        <div class="card-header">Collateral</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Type</th><th>Description</th><th>Valuation (UGX)</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($loan->collaterals as $collateral)
                        <tr>
                            <td>{{ $collateral->collateral_type }}</td>
                            <td>{{ $collateral->description }}</td>
                            <td>{{ number_format($collateral->valuation_amount, 2) }}</td>
                            <td>@if($collateral->is_released)<span class="badge bg-success">Released</span>@else<span class="badge bg-info">Held</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No collateral has been added for this loan yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Guarantors Card --}}
    <div class="card mb-4">
        <div class="card-header">Guarantor(s)</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Name</th><th>Phone Number</th><th>Address</th><th>Relationship</th></tr></thead>
                <tbody>
                    @forelse ($loan->guarantors as $guarantor)
                        <tr>
                            <td>{{ $guarantor->first_name }} {{ $guarantor->last_name }}</td>
                            <td>{{ $guarantor->phone_number }}</td>
                            <td>{{ $guarantor->address }}</td>
                            <td>{{ $guarantor->relationship_to_borrower }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No guarantors have been added for this loan yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment History Card --}}
    <div class="card mb-4">
        <div class="card-header">Payment History</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Date</th><th>Amount (UGX)</th><th>Method</th><th>Receipt #</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($loan->payments as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                            <td>{{ number_format($payment->amount_paid, 2) }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td>{{ $payment->receipt_number ?? 'N/A' }}</td>
                            <td><a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-secondary btn-sm" target="_blank">Receipt</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No payments have been recorded for this loan yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Repayment Schedule Card --}}
    <div class="card">
        <div class="card-header">Repayment Schedule</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>#</th><th>Due Date</th><th>Payment Amount</th><th>Principal</th><th>Interest</th><th>Remaining Balance</th></tr></thead>
                <tbody>
                    @foreach ($schedule as $payment)
                        <tr>
                            <td>{{ $payment['month'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment['due_date'])->format('F d, Y') }}</td>
                            <td>{{ number_format($payment['payment_amount'], 2) }}</td>
                            <td>{{ number_format($payment['principal'], 2) }}</td>
                            <td>{{ number_format($payment['interest'], 2) }}</td>
                            <td>{{ number_format($payment['remaining_balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Record Payment for Loan #{{ $loan->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                        <div class="mb-3">
                            <label for="amount_paid" class="form-label">Amount Paid (UGX)</label>
                            <input type="number" step="0.01" class="form-control" name="amount_paid" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Money">Mobile Money</option>
                            </select>
                        </div>
                         <div class="mb-3">
                            <label for="receipt_number" class="form-label">Receipt Number (Optional)</label>
                            <input type="text" class="form-control" name="receipt_number">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="guarantorModal" tabindex="-1" aria-labelledby="guarantorModalLabel" aria-hidden="true">
       <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guarantorModalLabel">Add Guarantor for Loan #{{ $loan->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('guarantors.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="nin" class="form-label">NIN (Optional)</label>
                            <input type="text" class="form-control" name="nin">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" required>
                        </div>
                         <div class="mb-3">
                            <label for="relationship_to_borrower" class="form-label">Relationship to Client</label>
                            <input type="text" class="form-control" name="relationship_to_borrower" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Guarantor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="collateralModal" tabindex="-1" aria-labelledby="collateralModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="collateralModalLabel">Add Collateral for Loan #{{ $loan->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('collaterals.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                        <div class="mb-3">
                            <label for="collateral_type" class="form-label">Type of Collateral</label>
                            <input type="text" class="form-control" name="collateral_type" placeholder="e.g., Land Title, Vehicle Logbook" required>
                        </div>
                         <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="e.g., Toyota Corolla, Reg No. UBA 123X" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="valuation_amount" class="form-label">Valuation Amount (UGX)</label>
                            <input type="number" step="0.01" class="form-control" name="valuation_amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="document_details" class="form-label">Document Details (Optional)</label>
                            <input type="text" class="form-control" name="document_details" placeholder="e.g., Block 20, Plot 15">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Collateral</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush