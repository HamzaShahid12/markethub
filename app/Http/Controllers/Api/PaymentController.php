<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CartResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    /**
     * Creates a Stripe PaymentIntent for the current cart's total,
     * so the card form can confirm payment client-side before the
     * order is actually created. Works for guests too — cart total
     * is the source of truth, not a logged-in user.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $cart = CartResolver::current($request);

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $cart->load('items');
        $subtotal = (float) $cart->items->sum(fn ($item) => $item->price * $item->quantity);
        $shippingFee = $subtotal >= 100 ? 0 : 10;

        $discount = 0.0;
        if ($request->string('coupon_code')->toString()) {
            $coupon = \App\Models\Coupon::whereRaw('upper(code) = ?', [strtoupper($request->string('coupon_code'))])->first();
            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $total = round($subtotal - $discount + $shippingFee, 2);

        $stripe = new StripeClient(config('services.stripe.secret'));

        $intent = $stripe->paymentIntents->create([
            'amount' => (int) round($total * 100), // Stripe uses smallest currency unit (cents)
            'currency' => 'usd',
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return response()->json([
            'client_secret' => $intent->client_secret,
            'total' => $total,
        ]);
    }
}