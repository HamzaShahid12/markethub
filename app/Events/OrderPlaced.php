<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched once a new order has fully committed (Phase 4's
 * CreateOrder action). Listeners handle the side effects — customer
 * confirmation, vendor new-order alerts, low-stock checks — off the
 * request/response cycle via the queue (section 12).
 */
class OrderPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
    }
}
