@extends('layouts.manager')

@section('title', 'Import Results')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Import Results</h1>
    <div>
        <a href="{{ route('clients.import') }}" class="btn btn-outline-secondary me-2">Import Another File</a>
        <a href="{{ route('clients.index') }}" class="btn btn-primary">Back to Clients</a>
    </div>
</div>

<div class="alert alert-{{ $created > 0 ? 'success' : 'warning' }}">
    <strong>{{ $created }}</strong> client{{ $created === 1 ? '' : 's' }} imported successfully.
    @if (count($skipped) > 0)
        <strong>{{ count($skipped) }}</strong> row{{ count($skipped) === 1 ? '' : 's' }} skipped — see details below.
    @endif
</div>

@if (count($skipped) > 0)
    <div class="card">
        <div class="card-header">Skipped Rows</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Name</th>
                        <th>Reason(s)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($skipped as $row)
                        <tr>
                            <td>{{ $row['row'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>
                                <ul class="mb-0 small">
                                    @foreach ($row['reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
