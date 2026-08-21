@extends('layouts.manager')

@section('title', 'Open Share Account')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Open Share Account</h1>
        <a href="{{ route('mfi.shares.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Share Accounts
        </a>
    </div>

    @if($products->isEmpty())
        <div class="alert alert-warning shadow-sm border-start border-warning border-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            You need at least one active Share Product before you can open share accounts.
            <a href="{{ route('mfi.products.create', ['type' => 'shares']) }}" class="fw-bold">Create one now</a>.
        </div>
    @else
    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-plus-circle me-2"></i>New Share Account</h6>
        </div>
        <div class="card-body p-4 bg-light">
            @if($errors->any())
                <div class="alert alert-danger shadow-sm border-start border-danger border-4 mb-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('mfi.shares.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Select Client</label>
                    <select name="client_id" id="clientSelect" class="form-select shadow-sm" style="width: 100%;" required>
                        <option value="" disabled selected>Search for a registered client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->phone_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Share Product</label>
                    <select name="mfi_product_id" class="form-select shadow-sm" required>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} — {{ number_format($product->share_value) }} / share</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Account Nickname <span class="text-secondary fw-normal">(Optional)</span></label>
                    <input type="text" name="nickname" class="form-control shadow-sm" value="{{ old('nickname') }}" placeholder="e.g. Founding Member Shares">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Number of Shares to Buy Now</label>
                    <input type="number" step="0.01" min="0" name="units" class="form-control shadow-sm" value="0">
                    <small class="text-muted mt-2 d-block">You can leave this at 0 and buy shares later from the account page.</small>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Open Account
                    </button>
                    <a href="{{ route('mfi.shares.index') }}" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#clientSelect').select2({
            placeholder: "Search for a client...",
            allowClear: true
        });
    });
</script>
@endpush
