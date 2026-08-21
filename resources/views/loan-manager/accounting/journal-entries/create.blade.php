@extends('layouts.manager')

@section('title', 'New Journal Entry')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">New Journal Entry</h1>
        <a href="{{ route('journal-entries.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to General Journal
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4 bg-light">
            <form method="POST" action="{{ route('journal-entries.store') }}" id="journalForm">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-dark">Date</label>
                        <input type="date" name="entry_date" class="form-control shadow-sm" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold text-dark">Reference <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="reference_no" class="form-control shadow-sm" value="{{ old('reference_no') }}" placeholder="e.g. cheque no, voucher no">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Narration</label>
                    <textarea name="narration" class="form-control shadow-sm" rows="2" placeholder="What is this entry for?">{{ old('narration') }}</textarea>
                </div>

                <label class="form-label fw-bold text-dark">Lines</label>
                <table class="table" id="linesTable">
                    <thead class="text-secondary small text-uppercase">
                        <tr>
                            <th style="width:32%;">Account</th>
                            <th>Description</th>
                            <th style="width:15%;">Debit</th>
                            <th style="width:15%;">Credit</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                        {{-- Two starter rows — a journal entry needs at least two lines. --}}
                        @for($i = 0; $i < 2; $i++)
                            <tr class="line-row">
                                <td>
                                    <select name="lines[{{ $i }}][chart_of_account_id]" class="form-select form-select-sm" required>
                                        <option value="">-- Select account --</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="lines[{{ $i }}][description]" class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control form-control-sm amount-input debit-input"></td>
                                <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control form-control-sm amount-input credit-input"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove line"><i class="fas fa-times"></i></button></td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end fw-bold">Totals:</td>
                            <td class="fw-bold font-monospace" id="totalDebit">0.00</td>
                            <td class="fw-bold font-monospace" id="totalCredit">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="5" id="balanceIndicator" class="small"></td>
                        </tr>
                    </tfoot>
                </table>

                <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="addRowBtn">
                    <i class="fas fa-plus me-1"></i> Add Line
                </button>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Post Entry
                    </button>
                    <a href="{{ route('journal-entries.index') }}" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var linesBody = document.getElementById('linesBody');
    var addRowBtn = document.getElementById('addRowBtn');
    var rowIndex = 2;
    var accountOptionsHtml = document.querySelector('.line-row select').innerHTML;

    function recalcTotals() {
        var totalDebit = 0, totalCredit = 0;
        document.querySelectorAll('.debit-input').forEach(function(el) { totalDebit += parseFloat(el.value) || 0; });
        document.querySelectorAll('.credit-input').forEach(function(el) { totalCredit += parseFloat(el.value) || 0; });

        document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
        document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);

        var indicator = document.getElementById('balanceIndicator');
        var diff = Math.round((totalDebit - totalCredit) * 100) / 100;
        if (diff === 0 && totalDebit > 0) {
            indicator.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Balanced.</span>';
        } else if (diff === 0) {
            indicator.innerHTML = '';
        } else {
            indicator.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Out of balance by ' + Math.abs(diff).toFixed(2) + '.</span>';
        }
    }

    function addRow() {
        var tr = document.createElement('tr');
        tr.className = 'line-row';
        tr.innerHTML =
            '<td><select name="lines[' + rowIndex + '][chart_of_account_id]" class="form-select form-select-sm" required>' + accountOptionsHtml + '</select></td>' +
            '<td><input type="text" name="lines[' + rowIndex + '][description]" class="form-control form-control-sm"></td>' +
            '<td><input type="number" step="0.01" min="0" name="lines[' + rowIndex + '][debit]" class="form-control form-control-sm amount-input debit-input"></td>' +
            '<td><input type="number" step="0.01" min="0" name="lines[' + rowIndex + '][credit]" class="form-control form-control-sm amount-input credit-input"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove line"><i class="fas fa-times"></i></button></td>';
        linesBody.appendChild(tr);
        rowIndex++;
    }

    addRowBtn.addEventListener('click', addRow);

    linesBody.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-row');
        if (!btn) return;
        if (linesBody.querySelectorAll('.line-row').length <= 2) {
            return; // keep at least two rows
        }
        btn.closest('tr').remove();
        recalcTotals();
    });

    linesBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('amount-input')) {
            // A line shouldn't have both a debit and a credit filled in.
            var row = e.target.closest('tr');
            if (e.target.classList.contains('debit-input') && parseFloat(e.target.value) > 0) {
                row.querySelector('.credit-input').value = '';
            } else if (e.target.classList.contains('credit-input') && parseFloat(e.target.value) > 0) {
                row.querySelector('.debit-input').value = '';
            }
            recalcTotals();
        }
    });
})();
</script>
@endpush
