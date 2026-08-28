<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Section 12: "Dispatch low-stock notifications." Database + live
 * broadcast by default — a vendor's inbox/bell badge is the right
 * place for this, not an inbox-flooding email per sale, but mail is
 * included too since the spec explicitly calls for queued
 * notifications and a vendor may want the email; toggling channels
 * is a one-line change if not.
 */
class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product, public int $remainingStock, public ?string $variantLabel = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock — {$this->product->name}")
            ->line("\"{$this->product->name}\"".($this->variantLabel ? " ({$this->variantLabel})" : '')." is down to {$this->remainingStock} unit(s).")
            ->action('Manage inventory', url('/vendor/inventory'));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'variant_label' => $this->variantLabel,
            'remaining_stock' => $this->remainingStock,
            'message' => "\"{$this->product->name}\"".($this->variantLabel ? " ({$this->variantLabel})" : '')." is down to {$this->remainingStock} left.",
            'url' => '/vendor/inventory',
        ];
    }
}
