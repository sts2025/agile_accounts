@extends('layouts.manager')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        {{-- Left Column --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    Date: {{ now()->format('d-m-Y') }}
                </div>
                <div class="card-body">
                    <h5 class="card-title">Cash at Hand</h5>
                    <p class="card-text fs-4 fw-bold">UGX {{ number_format($cashOnHand, 0) }}</p>
                </div>
                <div class="card-footer">
                    <button class="btn btn-success w-100">Send Report</button>
                </div>
            </div>

            {{-- Admin Announcement Card --}}
            @if($latestMessage)
                <div class="alert alert-info">
                    <h5 class="alert-heading">{{ $latestMessage->title }}</h5>
                    <p>{{ $latestMessage->body }}</p>
                    <hr>
                    <p class="mb-0"><small>Posted on: {{ $latestMessage->created_at->format('d F, Y') }}</small></p>
                </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Quick Actions</h5></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cashTransferModal">Add Payable / Receivable</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBankingModal">Add Banking</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">Add Expenses</button>
                    <a href="{{ route('manager.reports.daily-report') }}" class="btn btn-success">Daily Report</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Transactions for {{ \Carbon\Carbon::parse($reportDate)->format('d F, Y') }}</h5>
                    <a href="{{ route('manager.reports.daily-report.pdf', ['date' => $reportDate]) }}" class="btn btn-outline-secondary btn-sm" target="_blank">Print</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th colspan="2">Date: {{ \Carbon\Carbon::parse($reportDate)->format('d-m-Y') }}</th>
                                <th>No of Loans Given: {{ $loansGiven->count() }}</th>
                                <th>Loan Given: {{ number_format($totalLoanGiven, 0) }}</th>
                            </tr>
                            <tr>
                                <th colspan="2">Clients Paid: {{ $paymentsReceived->count() }}</th>
                                <th colspan="2">Total Loan Paid Cash: {{ number_format($totalPaidCash, 0) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Transactions</strong></td><td><strong>Cash_in</strong></td><td><strong>Cash_out</strong></td><td><strong>Closing Stock</strong></td></tr>
                            <tr><td>Opening Balance</td><td>{{ number_format($openingBalance, 0) }}</td><td></td><td></td></tr>
                            <tr><td>Cash In</td><td>{{ number_format($totalPaidCash, 0) }}</td><td></td><td></td></tr>
                            <tr><td>Cash Out</td><td></td><td>{{ number_format($totalLoanGiven, 0) }}</td><td></td></tr>
                            <tr class="table-light fw-bold">
                                <td colspan="3">Closing Stock</td>
                                <td>{{ number_format($closingStock, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- Add Expense Modal --}}
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add New Expense</h5></div>
            <form method="POST" action="{{ route('expenses.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="expense_date" value="{{ now()->toDateString() }}" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><input type="text" class="form-control" name="description" placeholder="e.g., Transport, Lunch Allowance" required></div>
                    <div class="mb-3"><label class="form-label">Amount (UGX)</label><input type="number" step="0.01" class="form-control" name="amount" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Expense</button></div>
            </form>
        </div></div>
    </div>

    {{-- Add Cash Transfer (Payable/Receivable) Modal --}}
    <div class="modal fade" id="cashTransferModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Record a Cash Transfer</h5></div>
            <form method="POST" action="{{ route('cash-transfers.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Transaction Type</label><select class="form-select" name="type" required><option value="in">Cash In (Receivable)</option><option value="out">Cash Out (Payable)</option></select></div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="transaction_date" value="{{ now()->toDateString() }}" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" placeholder="e.g., Transfer from/to Head Office" required></textarea></div>
                    <div class="mb-3"><label class="form-label">Amount (UGX)</label><input type="number" step="0.01" class="form-control" name="amount" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Transaction</button></div>
            </form>
        </div></div>
    </div>

    {{-- Add Banking Modal --}}
    <div class="modal fade" id="addBankingModal" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Record Bank Deposit</h5></div>
          <form method="POST" action="{{ route('banking.store') }}">
              @csrf
              <div class="modal-body">
                  <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="deposit_date" value="{{ now()->toDateString() }}" required></div>
                  <div class="mb-3"><label class="form-label">Amount Deposited (UGX)</label><input type="number" step="0.01" class="form-control" name="amount" required></div>
                  <div class="mb-3"><label class="form-label">Reference / Slip No. (Optional)</label><input type="text" class="form-control" name="reference_number"></div>
              </div>
              <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Deposit</button></div>
          </form>
      </div></div>
    </div>
@endpush