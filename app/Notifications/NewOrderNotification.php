<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a vendor's account when one of their products is part of a
 * new order (section 12: "Queue vendor new-order notifications").
 * Deliberately carries only that vendor's own item count/subtotal —
 * never the whole multi-vendor order — matching the ownership
 * boundary Phase 5 enforces everywhere else.
 */
class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public Vendor $vendor, public int $itemCount, public float $subtotal)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order — {$this->order->order_number}")
            ->greeting('You\'ve got a new order!')
            ->line("Order {$this->order->order_number} includes {$this->itemCount} item(s) from your shop.")
            ->line('Your subtotal: $'.number_format($this->subtotal, 2))
            ->action('View order', url("/vendor/orders/{$this->order->id}"));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'item_count' => $this->itemCount,
            'subtotal' => $this->subtotal,
            'message' => "New order {$this->order->order_number} — {$this->itemCount} item(s), \${$this->subtotal}",
            'url' => "/vendor/orders/{$this->order->id}",
        ];
    }
}
