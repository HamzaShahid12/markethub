<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Lightweight polling endpoint for the bell-icon unread badge —
 * mirrors the cart/wishlist stores' pattern (Phase 4) of a small JSON
 * endpoint the frontend fetches on mount rather than passing this
 * through every Inertia page's shared props.
 */
class NotificationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }
}
