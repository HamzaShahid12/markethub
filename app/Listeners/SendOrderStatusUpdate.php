<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->order->status === $event->previousStatus) {
            return;
        }

        $event->order->user->notify(new OrderStatusUpdatedNotification($event->order, $event->previousStatus));
    }
}
