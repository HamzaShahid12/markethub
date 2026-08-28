<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer once their order is placed (section 12:
 * "Queue order confirmation emails"). ShouldQueue means this is
 * dispatched to the queue worker, not sent inline during checkout.
 */
class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order confirmed — {$this->order->order_number}")
            ->greeting("Thanks for your order, {$notifiable->name}!")
            ->line("Your order {$this->order->order_number} has been placed.")
            ->line('Total: $'.number_format($this->order->total, 2))
            ->action('View order', url("/customer/orders/{$this->order->id}"))
            ->line('We\'ll let you know as soon as it ships.');
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_confirmation',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'message' => "Your order {$this->order->order_number} has been placed.",
            'url' => "/customer/orders/{$this->order->id}",
        ];
    }
}
