@extends('layouts.admin')

@section('title', 'Broadcast Messages')

@section('page_heading')
    Broadcast Messages
@endsection

@section('content')

    @if (session('status'))
        <div class="alert alert-info border-left-info shadow mb-4">
            <i class="fas fa-info-circle mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger shadow mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Create Form --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Send a New Broadcast Message</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small">
                Only one message can be active at a time — activating a new one automatically deactivates whichever
                one is currently live. Active messages appear at the top of every loan manager's dashboard.
            </p>
            <form method="POST" action="{{ route('admin.broadcasts.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Title / Subject</label>
                    <input type="text" class="form-control" name="title" id="title" value="{{ old('title') }}" required>
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">Message Body</label>
                    <textarea class="form-control" name="body" id="body" rows="5" required>{{ old('body') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane fa-sm mr-1"></i> Save Message
                </button>
            </form>
        </div>
    </div>

    {{-- Existing Messages --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">All Messages</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Title</th>
                            <th>Sent By</th>
                            <th>Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($messages as $message)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $message->title }}</div>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($message->body, 80) }}</div>
                                </td>
                                <td>{{ $message->user->name ?? 'Unknown' }}</td>
                                <td>{{ $message->created_at->format('d M, Y') }}</td>
                                <td class="text-center">
                                    @if ($message->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('admin.broadcasts.toggle', $message->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $message->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                            {{ $message->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.broadcasts.destroy', $message->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this broadcast message permanently?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No broadcast messages yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
