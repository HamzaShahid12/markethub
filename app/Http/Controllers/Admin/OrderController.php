<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->with('user:id,name')
            ->withCount('items')
            ->when($request->string('status')->toString(), fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where('order_number', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->string('status')->toString() ?: 'all',
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['user:id,name,email', 'items.vendor:id,shop_name', 'items.product:id,slug', 'coupon:id,code']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'customer' => $order->user,
                'coupon_code' => $order->coupon?->code,
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'shipping_fee' => $order->shipping_fee,
                'total' => $order->total,
                'shipping_address' => $order->shipping_address,
                'created_at' => $order->created_at->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'product_slug' => $item->product?->slug,
                    'vendor_name' => $item->vendor?->shop_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'status' => $item->status,
                ]),
            ],
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])],
        ]);

        $previousStatus = $order->status;
        $order->update(['status' => $data['status']]);

        if ($data['status'] !== $previousStatus) {
            \App\Events\OrderStatusChanged::dispatch($order, $previousStatus);
        }

        return back()->with('success', 'Order status updated.');
    }
}
