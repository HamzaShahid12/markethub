<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Admin overview shell (section 3.3: GMV, revenue, orders, customers,
     * vendors, pending approvals). Real analytics/reporting is built out
     * in the Admin phase — this wires the essential top-line counts so
     * the dashboard isn't empty while we build the rest.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'customers_count' => User::where('role', 'customer')->count(),
                'vendors_count' => Vendor::where('status', 'approved')->count(),
                'pending_vendors_count' => Vendor::where('status', 'pending')->count(),
                'orders_count' => Order::count(),
                'gmv' => Order::where('payment_status', 'paid')->sum('total'),
            ],
        ]);
    }
}
