<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CreateOrder;
use App\Http\Requests\CheckoutRequest;
use App\Support\CartResolver;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CheckoutController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $cart = CartResolver::current($request);

        if (! $cart || $cart->items->isEmpty()) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        $cart->load(['items.product.images', 'items.variant']);

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
            'isGuest' => ! $request->user(),
        ]);
    }

    public function store(CheckoutRequest $request, CreateOrder $createOrder): RedirectResponse
{
    $data = $request->validated();
    $cart = CartResolver::current($request);

    if (! $cart) {
        return to_route('cart.index')->with('error', 'Your cart is empty.');
    }

    try {
        $order = $createOrder->execute(
            cart: $cart,
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
            guestName: $data['name'] ?? null,
            guestEmail: $data['guest_email'] ?? null,
            paymentIntentId: $data['payment_intent_id'] ?? null,
        );
    } catch (\RuntimeException $e) {
        return back()->with('error', $e->getMessage());
    }

    return to_route('orders.success', $order->order_number);
}
}