@extends('layouts.app')

@section('title', 'Add New Client')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Add a New Client</h1>
            <hr>

            <form method="POST" action="{{ route('clients.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

    

                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Client</button>
                <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection