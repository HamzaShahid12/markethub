<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A single controller serves all three roles — Laravel's database
 * notifications are already scoped to `$user->notifications()`
 * regardless of role, so there's nothing role-specific to branch on
 * here. The Vue page picks its own layout (Customer/Vendor/Admin)
 * based on the logged-in user's role.
 */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(15);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'notification',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
