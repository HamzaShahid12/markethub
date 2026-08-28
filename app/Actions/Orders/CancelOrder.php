<?php

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cancels an order still early enough in its lifecycle to undo:
 * restores stock for every item, marks items and the order cancelled,
 * and removes the now-unearned vendor commissions. Called by both the
 * customer-facing "cancel my order" action and the admin order screen
 * — CancelOrderRequest-level authorization (who's allowed to cancel
 * this order) is OrderPolicy::cancel, not this class.
 */
class CancelOrder
{
    private const CANCELLABLE_STATUSES = ['pending', 'processing'];

    public function execute(Order $order): Order
    {
        if (! in_array($order->status, self::CANCELLABLE_STATUSES, true)) {
            throw new RuntimeException('This order can no longer be cancelled.');
        }

        $previousStatus = $order->status;

        $cancelled = DB::transaction(function () use ($order) {
            $order->load('items.product', 'items.variant', 'items.commission');

            foreach ($order->items as $item) {
                if ($item->product_variant_id && $item->variant) {
                    $item->variant->increment('stock', $item->quantity);
                } elseif ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }

                $item->product?->decrement('sold_count', min($item->quantity, $item->product->sold_count));
                $item->update(['status' => 'cancelled']);
                $item->commission?->delete();
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => $order->payment_status === 'paid' ? 'refunded' : $order->payment_status,
            ]);

            return $order->fresh('items');
        });

        \App\Events\OrderStatusChanged::dispatch($cancelled, $previousStatus);

        return $cancelled;
    }
}
