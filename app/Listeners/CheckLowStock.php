<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckLowStock implements ShouldQueue
{
    use InteractsWithQueue;

    private const THRESHOLD = 5;

    public function handle(OrderPlaced $event): void
    {
        $items = $event->order->items()->with(['product.vendor.user', 'variant.product'])->get();

        foreach ($items as $item) {
            if ($item->variant) {
                $this->maybeNotify($item->variant->product, $item->variant->stock, $item->variant->sku);
            } elseif ($item->product) {
                $this->maybeNotify($item->product, $item->product->stock);
            }
        }
    }

    private function maybeNotify(?\App\Models\Product $product, int $stock, ?string $variantLabel = null): void
    {
        if (! $product || $stock > self::THRESHOLD) {
            return;
        }

        $vendorUser = $product->vendor?->user;

        if ($vendorUser) {
            $vendorUser->notify(new LowStockNotification($product, $stock, $variantLabel));
        }
    }
}
