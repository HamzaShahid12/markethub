<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sales analytics (section 3.2: "Revenue, sales, top-products...
 * dashboard"). Reads straight off OrderItem — the historical snapshot
 * fields from Phase 4's checkout flow mean this stays accurate even
 * if a product is later edited or deleted.
 */
class SalesController extends Controller
{
    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $dailyRevenue = OrderItem::where('vendor_id', $vendor->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(subtotal) as revenue, SUM(quantity) as units')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = OrderItem::where('vendor_id', $vendor->id)
            ->selectRaw('product_id, product_name, SUM(quantity) as units_sold, SUM(subtotal) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->take(8)
            ->get();

        $recentSales = OrderItem::where('vendor_id', $vendor->id)
            ->with('order:id,order_number,created_at')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Vendor/Sales/Index', [
            'dailyRevenue' => $dailyRevenue,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'order_number' => $item->order->order_number,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'date' => $item->created_at->toDateString(),
            ]),
            'totals' => [
                'revenue_30d' => (float) $dailyRevenue->sum('revenue'),
                'units_30d' => (int) $dailyRevenue->sum('units'),
                'all_time_revenue' => (float) OrderItem::where('vendor_id', $vendor->id)->sum('subtotal'),
            ],
        ]);
    }
}
