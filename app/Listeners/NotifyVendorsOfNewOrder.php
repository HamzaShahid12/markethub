<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\NewOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyVendorsOfNewOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPlaced $event): void
    {
        $itemsByVendor = $event->order->items()->with('vendor.user')->get()->groupBy('vendor_id');

        foreach ($itemsByVendor as $items) {
            $vendor = $items->first()->vendor;

            if (! $vendor?->user) {
                continue;
            }

            $vendor->user->notify(new NewOrderNotification(
                order: $event->order,
                vendor: $vendor,
                itemCount: $items->sum('quantity'),
                subtotal: (float) $items->sum('subtotal'),
            ));
        }
    }
}
