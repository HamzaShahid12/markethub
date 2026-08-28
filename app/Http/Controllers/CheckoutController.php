<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CreateOrder;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CheckoutController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $cart = Cart::with(['items.product.images', 'items.variant'])->firstWhere('user_id', $request->user()->id);

        if (! $cart || $cart->items->isEmpty()) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Storefront/Checkout', [
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product->name,
                'variant_label' => $item->variant?->sku,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product->images->first()?->image
                    ? asset('storage/'.$item->product->images->first()->image)
                    : null,
            ]),
            'subtotal' => (float) $cart->items->sum(fn ($item) => $item->price * $item->quantity),
        ]);
    }

    public function store(CheckoutRequest $request, CreateOrder $createOrder): RedirectResponse
    {
        $data = $request->validated();

        try {
            $order = $createOrder->execute(
                user: $request->user(),
                shippingAddress: [
                    'name' => $data['name'],
                    'line1' => $data['line1'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'postal_code' => $data['postal_code'],
                    'country' => $data['country'],
                    'phone' => $data['phone'],
                ],
                couponCode: $data['coupon_code'] ?? null,
                paymentMethod: $data['payment_method'],
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('orders.success', $order->order_number);
    }
}
