@extends('layouts.manager')

@section('title', 'Penalty & Arrears Settings')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-exclamation-triangle text-warning me-2"></i> Penalty & Arrears Settings</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('loan-penalty-settings.update') }}">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary">Penalty Defaults</h6>
                <small class="text-muted">Used to pre-fill the amount when staff add a penalty to a loan — penalties are still applied manually, one loan at a time.</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-dark">Penalty Type</label>
                        <select name="penalty_type" class="form-select shadow-sm" id="penaltyType">
                            <option value="flat" {{ $settings->penalty_type === 'flat' ? 'selected' : '' }}>Flat Amount</option>
                            <option value="percent_overdue" {{ $settings->penalty_type === 'percent_overdue' ? 'selected' : '' }}>% of Outstanding Balance</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="flatAmountField">
                        <label class="form-label fw-bold text-dark">Flat Amount</label>
                        <input type="number" step="0.01" name="penalty_amount" class="form-control shadow-sm" value="{{ $settings->penalty_amount }}">
                    </div>
                    <div class="col-md-4 mb-3" id="percentField">
                        <label class="form-label fw-bold text-dark">Percent of Balance</label>
                        <input type="number" step="0.01" name="penalty_percent" class="form-control shadow-sm" value="{{ $settings->penalty_percent }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold text-dark">Grace Period (Days)</label>
                        <input type="number" name="grace_period_days" class="form-control shadow-sm" value="{{ $settings->grace_period_days }}">
                        <small class="text-muted">Days a payment can be late before it's considered penalty-eligible.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary">Arrears Age & Provision Rate</h6>
                <small class="text-muted">How much of an overdue loan's balance to provision for as a probable loss, by how many days late it is. Used by the Balance Sheet's non-performing loan figures.</small>
            </div>
            <div class="card-body">
                @php $rates = $settings->provision_rates ?? []; @endphp
                <table class="table table-sm" id="bucketsTable">
                    <thead class="text-secondary small text-uppercase">
                        <tr>
                            <th>Days Late (or more)</th>
                            <th>Provision Rate (%)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bucketsBody">
                        @forelse($rates as $days => $rate)
                            <tr>
                                <td><input type="number" name="bucket_days[]" class="form-control form-control-sm" value="{{ $days }}"></td>
                                <td><input type="number" step="0.01" name="bucket_rates[]" class="form-control form-control-sm" value="{{ $rate }}"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-bucket"><i class="fas fa-times"></i></button></td>
                            </tr>
                        @empty
                            @foreach([30, 60, 90] as $default)
                                <tr>
                                    <td><input type="number" name="bucket_days[]" class="form-control form-control-sm" value="{{ $default }}"></td>
                                    <td><input type="number" step="0.01" name="bucket_rates[]" class="form-control form-control-sm"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-bucket"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforeach
                        @endforelse
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addBucketBtn"><i class="fas fa-plus me-1"></i> Add Bucket</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
            <i class="fas fa-save me-2"></i> Save Settings
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var typeSelect = document.getElementById('penaltyType');
    var flatField = document.getElementById('flatAmountField');
    var percentField = document.getElementById('percentField');

    function toggleFields() {
        if (typeSelect.value === 'flat') {
            flatField.style.display = '';
            percentField.style.display = 'none';
        } else {
            flatField.style.display = 'none';
            percentField.style.display = '';
        }
    }
    typeSelect.addEventListener('change', toggleFields);
    toggleFields();

    document.getElementById('addBucketBtn').addEventListener('click', function() {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="number" name="bucket_days[]" class="form-control form-control-sm"></td>' +
            '<td><input type="number" step="0.01" name="bucket_rates[]" class="form-control form-control-sm"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-bucket"><i class="fas fa-times"></i></button></td>';
        document.getElementById('bucketsBody').appendChild(tr);
    });

    document.getElementById('bucketsBody').addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-bucket');
        if (btn) btn.closest('tr').remove();
    });
})();
</script>
@endpush
