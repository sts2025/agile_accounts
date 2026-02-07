@extends('layouts.app')

@section('title', 'Print Forms & Agreements')

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Print Forms</h1>
    </div>

    {{-- Search Card: Added for filtering --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Search Client Agreement</h6>
        </div>
        <div class="card-body">
            <div class="input-group input-group-lg">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-primary text-white border-primary">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                {{-- This input triggers the search logic --}}
                <input type="text" id="clientSearchInput" class="form-control border-primary" placeholder="Type client name here to find their forms..." autofocus>
            </div>
            <small class="text-muted mt-2 d-block">Search through {{ $clientsWithLoans->count() }} clients with registered loans.</small>
        </div>
    </div>

    {{-- Accordion List --}}
    <div class="accordion shadow" id="clientLoansAccordion">
        @forelse($clientsWithLoans as $client)
            {{-- Added 'client-item' class and 'data-name' for JS filtering --}}
            <div class="card client-item" data-name="{{ strtolower($client->name) }}">
                <div class="card-header p-0" id="heading-{{ $client->id }}">
                    <button class="btn btn-link btn-block text-left text-dark font-weight-bold py-3 px-4 d-flex justify-content-between align-items-center" 
                            type="button" data-toggle="collapse" data-target="#collapse-{{ $client->id }}" aria-expanded="false" style="text-decoration: none;">
                        <span>
                            <i class="fas fa-user-circle mr-2 text-primary"></i>
                            {{ $client->name }} 
                            <span class="badge badge-secondary ml-2">{{ $client->loans->count() }} Loan(s)</span>
                        </span>
                        <i class="fas fa-chevron-down fa-sm text-gray-400"></i>
                    </button>
                </div>

                <div id="collapse-{{ $client->id }}" class="collapse" aria-labelledby="heading-{{ $client->id }}" data-parent="#clientLoansAccordion">
                    <div class="card-body bg-light">
                        <ul class="list-group">
                            @foreach($client->loans as $loan)
                                <li class="list-group-item d-flex justify-content-between align-items-center shadow-sm mb-2">
                                    <div>
                                        <div class="font-weight-bold text-primary">Loan #{{ $loan->id }} - {{ Auth::user()->loanManager->currency_symbol ?? 'UGX' }} {{ number_format($loan->principal_amount) }}</div>
                                        <small class="text-muted">
                                            Date: {{ \Carbon\Carbon::parse($loan->start_date)->format('M d, Y') }} | 
                                            Status: <span class="badge badge-light border">{{ strtoupper($loan->status) }}</span>
                                        </small>
                                    </div>
                                    <a href="{{ route('loans.downloadAgreement', $loan->id) }}" class="btn btn-primary btn-sm px-3" target="_blank">
                                        <i class="fas fa-print mr-1"></i> Print Agreement
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info shadow-sm">
                <i class="fas fa-info-circle mr-2"></i> No clients with loans were found.
            </div>
        @endforelse
    </div>

    {{-- No Results placeholder --}}
    <div id="noResults" class="text-center p-5 d-none">
        <i class="fas fa-search-minus fa-3x text-gray-300 mb-3"></i>
        <h5 class="text-gray-500">No matching clients found.</h5>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('clientSearchInput');
        const clientItems = document.querySelectorAll('.client-item');
        const noResults = document.getElementById('noResults');
        const accordionContainer = document.getElementById('clientLoansAccordion');

        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            clientItems.forEach(item => {
                const clientName = item.getAttribute('data-name');
                if (clientName.includes(query)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    // Optional: collapse the item if it was open while being hidden
                    $(item).find('.collapse').collapse('hide');
                }
            });

            // Handle "No Results" display
            if (visibleCount === 0 && query !== '') {
                noResults.classList.remove('d-none');
                accordionContainer.classList.add('d-none');
            } else {
                noResults.classList.add('d-none');
                accordionContainer.classList.remove('d-none');
            }
        });
    });
</script>
<style>
    .client-item .btn-link:focus, .client-item .btn-link:hover {
        background-color: #f8f9fc;
    }
</style>
@endpush

@endsection