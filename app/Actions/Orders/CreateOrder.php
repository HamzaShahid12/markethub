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

/**
 * The transactional checkout flow from spec section 11:
 *
 *   Validate cart -> validate stock -> calculate totals -> validate
 *   coupon -> DB::transaction { create order, create order items,
 *   decrement stock, calculate commissions, clear cart }.
 *
 * Kept as a single Action (per the app/Actions/Orders/ structure in
 * section 7) so both the web checkout controller and, later, an API
 * client can call the exact same order-creation logic.
 */
class CreateOrder
{
    public function execute(User $user, array $shippingAddress, ?string $couponCode, string $paymentMethod): Order
    {
        $cart = Cart::with(['items.product', 'items.variant'])->firstWhere('user_id', $user->id);

        if (! $cart || $cart->items->isEmpty()) {
            throw new RuntimeException('Your cart is empty.');
        }

        // Validate stock before we ever touch the database in a transaction.
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

        $order = DB::transaction(function () use ($user, $cart, $subtotal, $discount, $shippingFee, $total, $coupon, $shippingAddress, $paymentMethod) {
            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'order_number' => 'MH-'.strtoupper(Str::random(10)),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => $paymentMethod === 'cod' ? 'unpaid' : 'paid',
                'payment_method' => $paymentMethod,
                'shipping_address' => $shippingAddress,
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
                    // Historical snapshot — later product edits never alter this order (section 6.3).
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

        // Dispatched after the transaction commits, so queued listeners
        // (confirmation email, vendor alerts, low-stock check) never
        // fire against an order that could still roll back.
        \App\Events\OrderPlaced::dispatch($order);

        return $order;
    }
}
