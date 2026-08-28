<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A multi-vendor order can contain items from several shops. Every
 * query and mutation here is scoped to the authenticated vendor's own
 * OrderItem rows — a vendor never sees or touches another vendor's
 * items within the same order (section 6.3 ownership rule).
 */
class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $orderIds = OrderItem::where('vendor_id', $vendor->id)->distinct()->pluck('order_id');

        $orders = Order::whereIn('id', $orderIds)
            ->with(['user:id,name', 'items' => fn ($q) => $q->where('vendor_id', $vendor->id)])
            ->latest()
            ->paginate(10)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user->name,
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
                'my_items_count' => $order->items->count(),
                'my_subtotal' => $order->items->sum('subtotal'),
            ]);

        return Inertia::render('Vendor/Orders/Index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $vendor = $request->user()->vendor;

        $items = $order->items()->where('vendor_id', $vendor->id)->with('product:id,slug')->get();

        return Inertia::render('Vendor/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
                'customer_name' => $order->user->name,
                'shipping_address' => $order->shipping_address,
                'items' => $items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'product_slug' => $item->product?->slug,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'status' => $item->status,
                ]),
            ],
        ]);
    }

    public function updateItemStatus(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->authorize('updateStatus', $order);

        abort_unless($item->vendor_id === $request->user()->vendor?->id, 403);
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'shipped', 'delivered', 'cancelled'])],
        ]);

        $item->update(['status' => $data['status']]);

        $this->syncOrderStatus($order);

        return back()->with('success', 'Item status updated.');
    }

    /**
     * The parent Order's status reflects the least-progressed item
     * across all vendors: an order isn't "delivered" until every
     * vendor's items are delivered, but moves past "pending" as soon
     * as any vendor starts processing their part.
     */
    private function syncOrderStatus(Order $order): void
    {
        $previousStatus = $order->status;
        $statuses = $order->items()->pluck('status');

        $nextStatus = match (true) {
            $statuses->every(fn ($s) => $s === 'delivered') => 'delivered',
            $statuses->every(fn ($s) => in_array($s, ['delivered', 'cancelled'], true)) => 'delivered',
            $statuses->contains('shipped') => 'shipped',
            $statuses->contains('processing') => 'processing',
            default => $order->status,
        };

        $order->update(['status' => $nextStatus]);

        if ($nextStatus !== $previousStatus) {
            \App\Events\OrderStatusChanged::dispatch($order, $previousStatus);
        }
    }
}
