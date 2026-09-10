@extends('layouts.manager')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark fw-bold"><i class="fas fa-bell text-secondary me-2"></i> Notifications</h1>
        @if ($notifications->where('read_at', null)->count() > 0)
            <form method="POST" action="{{ route('notifications.markAllRead') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="card shadow-sm border-0">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item d-flex justify-content-between align-items-start {{ $notification->read_at ? '' : 'bg-light' }}">
                    <div class="me-3">
                        <div class="fw-bold">
                            @unless ($notification->read_at)
                                <span class="badge bg-primary me-1">New</span>
                            @endunless
                            {{ $notification->title }}
                        </div>
                        <div class="small text-muted">{{ $notification->body }}</div>
                        <div class="small text-muted mt-1">{{ $notification->created_at?->format('d-M-Y H:i') }}</div>
                    </div>
                    <form method="POST" action="{{ route('notifications.markRead', $notification->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            {{ $notification->read_at ? 'View' : 'Mark read' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-4">No notifications yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
