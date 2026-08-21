@extends('layouts.manager')

@section('title', 'Edit Client')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold">Edit Client: {{ $client->name }}</h1>
        <a href="{{ route('clients.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Clients
        </a>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 800px;">
        <div class="card-body p-4">

            @if($errors->any())
                <div class="alert alert-danger shadow-sm border-start border-danger border-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('clients.update', $client->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Account Type</label>
                        <select name="client_type" id="clientTypeSelect" class="form-select">
                            <option value="individual" {{ old('client_type', $client->client_type) == 'individual' ? 'selected' : '' }}>Individual</option>
                            <option value="business" {{ old('client_type', $client->client_type) == 'business' ? 'selected' : '' }}>Business</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3" id="businessFields">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Business Name</label>
                        <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $client->business_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Registration Number <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="business_registration_number" class="form-control" value="{{ old('business_registration_number', $client->business_registration_number) }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $client->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $client->phone_number) }}" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">National ID (NIN) <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="text" name="national_id" class="form-control" value="{{ old('national_id', $client->national_id) }}" placeholder="e.g. CM12345678">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Date of Birth <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Gender <span class="text-secondary fw-normal">(Optional)</span></label>
                        <select name="gender" class="form-select">
                            <option value="" selected>-- Select --</option>
                            <option value="Male" {{ old('gender', $client->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $client->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender', $client->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Email Address <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">Address</label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $client->address) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small">Business / Occupation <span class="text-secondary fw-normal">(Optional)</span></label>
                    <input type="text" name="business_occupation" class="form-control" value="{{ old('business_occupation', $client->business_occupation) }}">
                </div>

                <div class="border-top pt-3 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-cog me-2"></i>Account Settings</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">Assigned Officer <span class="text-secondary fw-normal">(Optional)</span></label>
                            <select name="assigned_user_id" class="form-select">
                                <option value="">-- Unassigned --</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ old('assigned_user_id', $client->assigned_user_id) == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">Preferred Notification</label>
                            <select name="preferred_notification_channel" class="form-select">
                                <option value="sms" {{ old('preferred_notification_channel', $client->preferred_notification_channel ?? 'sms') == 'sms' ? 'selected' : '' }}>SMS</option>
                                <option value="email" {{ old('preferred_notification_channel', $client->preferred_notification_channel) == 'email' ? 'selected' : '' }}>Email</option>
                                <option value="none" {{ old('preferred_notification_channel', $client->preferred_notification_channel) == 'none' ? 'selected' : '' }}>None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-id-card me-2"></i>KYC Documents</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">Passport Photo <span class="text-secondary fw-normal">(Optional)</span></label>
                            @if($client->photo_path)
                                <div class="mb-2"><img src="{{ route('clients.photo', $client) }}" alt="Current photo" class="rounded" style="max-height: 100px;"></div>
                            @endif
                            <input type="file" name="photo" accept="image/png,image/jpeg" class="form-control">
                            <small class="text-muted d-block mt-1">Uploading a new photo replaces the current one. JPG or PNG, max 2MB.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">ID Document <span class="text-secondary fw-normal">(Optional)</span></label>
                            @if($client->id_document_path)
                                <div class="mb-2"><a href="{{ route('clients.id-document', $client) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file me-1"></i> View Current Document</a></div>
                            @endif
                            <input type="file" name="id_document" accept="image/png,image/jpeg,application/pdf" class="form-control">
                            <small class="text-muted d-block mt-1">Uploading a new file replaces the current one. JPG, PNG, or PDF, max 4MB.</small>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user-friends me-2"></i>Next of Kin <span class="text-secondary fw-normal small">(Optional)</span></h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">Full Name</label>
                            <input type="text" name="next_of_kin_name" class="form-control" value="{{ old('next_of_kin_name', $client->next_of_kin_name) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">Phone Number</label>
                            <input type="text" name="next_of_kin_phone" class="form-control" value="{{ old('next_of_kin_phone', $client->next_of_kin_phone) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">Relationship</label>
                            <input type="text" name="next_of_kin_relationship" class="form-control" value="{{ old('next_of_kin_relationship', $client->next_of_kin_relationship) }}" placeholder="e.g. Spouse, Parent">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 border-top pt-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Update Client
                    </button>
                    <a href="{{ route('clients.index') }}" class="btn btn-light fw-bold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        var typeSelect = document.getElementById('clientTypeSelect');
        var businessFields = document.getElementById('businessFields');

        function toggleBusinessFields() {
            businessFields.style.display = typeSelect.value === 'business' ? '' : 'none';
        }
        typeSelect.addEventListener('change', toggleBusinessFields);
        toggleBusinessFields();
    })();
</script>
@endpush
@endsection