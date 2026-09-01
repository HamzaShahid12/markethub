<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * The order confirmation page. No login required — the order
     * number itself (a random 10-character token, effectively
     * unguessable) is what proves this visitor just placed this
     * order, whether they're logged in, a pure guest, or a guest
     * whose email happened to match an existing account.
     */
    public function success(Request $request, string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();

        return Inertia::render('Storefront/OrderSuccess', [
            'order' => [
                'order_number' => $order->order_number,
                'total' => $order->total,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'shipping_address' => $order->shipping_address,
                'is_guest' => $order->user_id === null,
                'guest_email' => $order->guest_email,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]),
            ],
        ]);
    }
}