<?php

namespace App\Http\Controllers\LoanManager;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Notification inbox for the logged-in user. Unlike most controllers in
 * this app, scoping here is per-user (not per-tenant) — each recipient
 * only ever sees notifications addressed to them, via
 * NotificationService::resolveRecipients() at write time.
 */
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AppNotification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('loan-manager.notifications.index', compact('notifications'));
    }

    public function markRead(AppNotification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->markRead();

        return $notification->url
            ? redirect($notification->url)
            : back();
    }

    public function markAllRead()
    {
        AppNotification::where('user_id', Auth::id())
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
