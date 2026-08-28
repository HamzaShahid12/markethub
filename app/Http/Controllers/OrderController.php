<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Only the post-checkout confirmation page is built in this phase.
 * Order history/detail/status-timeline for customers and vendors is
 * Phase 5 (Orders) — kept as a separate, additive controller so this
 * class only grows, never gets rewritten.
 */
class OrderController extends Controller
{
    public function success(Request $request, string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();

        $this->authorize('view', $order);

        return Inertia::render('Storefront/OrderSuccess', [
            'order' => [
                'order_number' => $order->order_number,
                'total' => $order->total,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'shipping_address' => $order->shipping_address,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]),
            ],
        ]);
    }
}
