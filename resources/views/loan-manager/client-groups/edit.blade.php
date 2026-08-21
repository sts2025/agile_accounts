@extends('layouts.manager')

@section('title', 'Edit Client Group')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Edit Group: {{ $group->name }}</h1>
        <a href="{{ route('client-groups.show', $group->id) }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Group
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 700px;">
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

            <form action="{{ route('client-groups.update', $group->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Group Name</label>
                    <input type="text" name="name" class="form-control shadow-sm" value="{{ old('name', $group->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Description <span class="text-secondary fw-normal">(Optional)</span></label>
                    <textarea name="description" class="form-control shadow-sm" rows="2">{{ old('description', $group->description) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Members</label>
                    <select name="members[]" id="memberSelect" class="form-select shadow-sm" style="width: 100%;" multiple>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ in_array($client->id, old('members', $memberIds)) ? 'selected' : '' }}>
                                {{ $client->name }} ({{ $client->phone_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                    <a href="{{ route('client-groups.show', $group->id) }}" class="btn btn-light fw-bold py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#memberSelect').select2({
            placeholder: "Search and select clients...",
            allowClear: true
        });
    });
</script>
@endpush
