<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-wide analytics (section 3.3: "Analytics and system
 * settings"). Deliberately read-only aggregate queries — nothing here
 * mutates data, so it's safe to run often / cache later if it gets slow.
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dailyGmv = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $topVendors = OrderItem::selectRaw('vendor_id, SUM(subtotal) as revenue')
            ->with('vendor:id,shop_name')
            ->groupBy('vendor_id')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->vendor->shop_name, 'revenue' => (float) $row->revenue]);

        $topCategories = OrderItem::query()
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category_name, SUM(order_items.subtotal) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Reports/Index', [
            'dailyGmv' => $dailyGmv,
            'ordersByStatus' => $ordersByStatus,
            'topVendors' => $topVendors,
            'topCategories' => $topCategories,
            'summary' => [
                'total_vendors' => Vendor::where('status', 'approved')->count(),
                'total_gmv' => (float) Order::where('payment_status', 'paid')->sum('total'),
                'avg_order_value' => (float) (Order::where('payment_status', 'paid')->avg('total') ?? 0),
            ],
        ]);
    }
}
