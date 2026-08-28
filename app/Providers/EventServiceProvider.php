<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Listeners\CheckLowStock;
use App\Listeners\NotifyVendorsOfNewOrder;
use App\Listeners\SendOrderConfirmation;
use App\Listeners\SendOrderStatusUpdate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel 12's skeleton no longer ships an EventServiceProvider by
 * default (events with a matching Listener naming convention
 * auto-discover), but our listener names don't match their event
 * 1:1 (three listeners on one event), so they're registered here
 * explicitly. Register this provider in bootstrap/providers.php —
 * see the README for the exact line.
 */
class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(OrderPlaced::class, SendOrderConfirmation::class);
        Event::listen(OrderPlaced::class, NotifyVendorsOfNewOrder::class);
        Event::listen(OrderPlaced::class, CheckLowStock::class);

        Event::listen(OrderStatusChanged::class, SendOrderStatusUpdate::class);
    }
}
