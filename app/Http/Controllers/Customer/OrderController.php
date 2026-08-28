<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Orders\CancelOrder;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Orders/Index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['items.product:id,slug', 'items.vendor:id,shop_name']);

        $reviewedProductIds = Review::where('user_id', $request->user()->id)
            ->where('order_id', $order->id)
            ->pluck('product_id');

        return Inertia::render('Customer/Orders/Show', [
            'order' => $this->transform($order, $reviewedProductIds),
        ]);
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        $this->authorize('cancel', $order);

        try {
            $cancelOrder->execute($order);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order cancelled.');
    }

    private function transform(Order $order, $reviewedProductIds = null): array
    {
        $reviewedProductIds ??= collect();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'shipping_fee' => $order->shipping_fee,
            'total' => $order->total,
            'shipping_address' => $order->shipping_address,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_slug' => $item->product?->slug,
                'vendor_name' => $item->vendor?->shop_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
                'status' => $item->status,
                'can_review' => $item->status === 'delivered'
                    && $item->product_id
                    && ! $reviewedProductIds->contains($item->product_id),
            ]),
        ];
    }
}
