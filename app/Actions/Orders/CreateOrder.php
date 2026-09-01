<?php

namespace App\Actions\Orders;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\StripeClient;

class CreateOrder
{
    public function execute(
        Cart $cart,
        ?User $user,
        array $shippingAddress,
        ?string $couponCode,
        string $paymentMethod,
        ?string $guestName = null,
        ?string $guestEmail = null,
        ?string $paymentIntentId = null,
    ): Order {
        $cart->load(['items.product', 'items.variant']);

        if (! $user && $guestEmail) {
            $existingUser = User::where('email', $guestEmail)->first();
            if ($existingUser) {
                $user = $existingUser;
            }
        }

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Your cart is empty.');
        }

        foreach ($cart->items as $item) {
            $available = $item->variant?->stock ?? $item->product->stock;
            if ($item->quantity > $available) {
                throw new RuntimeException("\"{$item->product->name}\" only has {$available} left in stock.");
            }
        }

        $subtotal = (float) $cart->items->sum(fn ($item) => $item->price * $item->quantity);

        $coupon = null;
        $discount = 0.0;

        if ($couponCode) {
            $coupon = Coupon::whereRaw('upper(code) = ?', [strtoupper($couponCode)])->first();

            if (! $coupon || ! $coupon->isValidFor($subtotal)) {
                throw new RuntimeException('That coupon can no longer be applied to this order.');
            }

            $discount = $coupon->calculateDiscount($subtotal);
        }

        $shippingFee = $subtotal >= 100 ? 0 : 10;
        $total = round($subtotal - $discount + $shippingFee, 2);

        // For card payments, verify the PaymentIntent actually succeeded
        // server-side before creating the order — never trust the
        // client's word that a card payment went through.
        $paymentStatus = 'unpaid';

        if ($paymentMethod === 'card') {
            if (! $paymentIntentId) {
                throw new RuntimeException('Payment information is missing.');
            }

            $stripe = new StripeClient(config('services.stripe.secret'));
            $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

            if ($intent->status !== 'succeeded') {
                throw new RuntimeException('Payment was not completed. Please try again.');
            }

            if ((int) round($total * 100) !== $intent->amount) {
                throw new RuntimeException('Payment amount does not match your order total.');
            }

            $paymentStatus = 'paid';
        } elseif ($paymentMethod !== 'cod') {
            $paymentStatus = 'paid';
        }

        $order = DB::transaction(function () use ($user, $cart, $subtotal, $discount, $shippingFee, $total, $coupon, $shippingAddress, $paymentMethod, $guestName, $guestEmail, $paymentStatus, $paymentIntentId) {
            $order = Order::create([
                'user_id' => $user?->id,
                'guest_name' => $user ? null : $guestName,
                'guest_email' => $user ? null : $guestEmail,
                'coupon_id' => $coupon?->id,
                'order_number' => 'MH-'.strtoupper(Str::random(10)),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'shipping_address' => $shippingAddress,
                'notes' => $paymentIntentId ? "Stripe PaymentIntent: {$paymentIntentId}" : null,
            ]);

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product()->lockForUpdate()->first();
                $variant = $cartItem->variant()->lockForUpdate()->first();

                $available = $variant?->stock ?? $product->stock;
                if ($cartItem->quantity > $available) {
                    throw new RuntimeException("\"{$product->name}\" sold out while you were checking out.");
                }

                $lineSubtotal = $cartItem->price * $cartItem->quantity;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'vendor_id' => $product->vendor_id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'subtotal' => $lineSubtotal,
                    'status' => 'pending',
                ]);

                if ($variant) {
                    $variant->decrement('stock', $cartItem->quantity);
                } else {
                    $product->decrement('stock', $cartItem->quantity);
                }
                $product->increment('sold_count', $cartItem->quantity);

                $vendor = $product->vendor;
                $rate = (float) $vendor->commission_rate;
                $commissionAmount = round($lineSubtotal * ($rate / 100), 2);

                VendorCommission::create([
                    'vendor_id' => $vendor->id,
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'order_amount' => $lineSubtotal,
                    'commission_rate' => $rate,
                    'commission_amount' => $commissionAmount,
                    'vendor_amount' => $lineSubtotal - $commissionAmount,
                    'status' => 'pending',
                ]);
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $cart->items()->delete();

            return $order->load('items');
        });

        \App\Events\OrderPlaced::dispatch($order);

        return $order;
    }
}