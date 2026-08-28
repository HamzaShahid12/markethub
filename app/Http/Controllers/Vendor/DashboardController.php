<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Vendor dashboard shell. A pending vendor (awaiting admin approval,
     * section 3.2) sees an approval-status state instead of the full
     * sales dashboard — they still get a working page, just gated on
     * what they're allowed to do until approved.
     */
    public function __invoke(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        if (! $vendor) {
            abort(403, 'No vendor profile is associated with this account.');
        }

        $stats = $vendor->isApproved()
            ? [
                'products_count' => $vendor->products()->count(),
                'orders_count' => $vendor->orderItems()->distinct('order_id')->count('order_id'),
                'total_earnings' => $vendor->commissions()->sum('vendor_amount'),
                'pending_commissions' => $vendor->commissions()->where('status', 'pending')->sum('vendor_amount'),
            ]
            : null;

        return Inertia::render('Vendor/Dashboard', [
            'vendor' => $vendor->only(['id', 'shop_name', 'slug', 'status', 'commission_rate']),
            'stats' => $stats,
        ]);
    }
}
