<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Customer dashboard shell (section 5: Dashboard, Orders, Order
     * Details, Wishlist, Addresses, Reviews, Notifications, Profile).
     * Real order/wishlist data is wired in the Shopping/Orders phases —
     * this establishes the role-gated page and layout.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Customer/Dashboard', [
            'stats' => [
                'orders_count' => $user->orders()->count(),
                'wishlist_count' => $user->wishlist?->items()->count() ?? 0,
                'reviews_count' => $user->reviews()->count(),
            ],
            'recentOrders' => $user->orders()
                ->latest()
                ->take(5)
                ->get(['id', 'order_number', 'status', 'total', 'created_at']),
        ]);
    }
}