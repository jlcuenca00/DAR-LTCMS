<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->systemNotifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $unreadCount = $request->user()
            ->systemNotifications()
            ->whereNull('read_at')
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function open(Request $request, SystemNotification $notification)
    {
        Gate::authorize('view', $notification);

        $notification->markAsRead();

        return redirect()->to($notification->targetUrlFor($request->user()));
    }

    public function markAsRead(Request $request, SystemNotification $notification)
    {
        Gate::authorize('view', $notification);

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markVisibleAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1', 'max:5'],
            'notification_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        // Always scope through the recipient; supplied IDs cannot change another user's state.
        $readIds = $request->user()->systemNotifications()
            ->whereIn('id', $validated['notification_ids'])->pluck('id')->all();

        $request->user()->systemNotifications()
            ->whereIn('id', $readIds)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'read_ids' => $readIds,
            'unread_count' => $request->user()->systemNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->systemNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
