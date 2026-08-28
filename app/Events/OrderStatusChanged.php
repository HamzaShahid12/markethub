<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched whenever an order's overall status changes — a vendor
 * moving their items along (Phase 5), an admin override, or a
 * cancellation. Section 12: "Use events for order status changes."
 */
class OrderStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order, public string $previousStatus)
    {
    }
}
