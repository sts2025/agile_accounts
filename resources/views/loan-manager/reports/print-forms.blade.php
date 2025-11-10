@extends('layouts.manager')
@section('title', 'Print Forms')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Print Forms</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select a Loan Agreement</h6>
        </div>
        <div class="card-body">
            <p>Select a client from the list to see their available loan agreements to print.</p>

            <div class="accordion" id="clientLoansAccordion">
                
                @forelse($clientsWithLoans as $client)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $client->id }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $client->id }}" aria-expanded="false" aria-controls="collapse-{{ $client->id }}">
                                <strong>{{ $client->name }}</strong> ({{ $client->loans->count() }} Loan(s))
                            </button>
                        </h2>
                        <div id="collapse-{{ $client->id }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $client->id }}" data-bs-parent="#clientLoansAccordion">
                            <div class="accordion-body">
                                <ul class="list-group">
                                    @foreach($client->loans as $loan)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                Loan #{{ $loan->id }} - UGX {{ number_format($loan->principal_amount) }}
                                                <small class="d-block text-muted">Date: {{ Carbon\Carbon::parse($loan->start_date)->format('M d, Y') }} | Status: <span class="badge bg-secondary">{{ $loan->status }}</span></Fsmall>
                                            </div>
                                            <a href="{{ route('loans.downloadAgreement', $loan->id) }}" class="btn btn-primary btn-sm" target="_blank">
                                                <i class="fas fa-print me-1"></i> Print
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info">
                        No clients with loans were found.
                    </div>
                @endforelse

            </div>
        </div>
    </div>
@endsection
