<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Inbox-level awareness that a new chat message arrived — distinct
 * from MessageSent (which only updates an already-open thread live).
 * Database + broadcast only, no email: a chat message isn't urgent
 * enough to justify inbox-flooding mail per message.
 */
class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Conversation $conversation, public Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        $url = $notifiable->role === 'vendor'
            ? "/vendor/messages/{$this->conversation->id}"
            : "/customer/messages/{$this->conversation->id}";

        return [
            'type' => 'new_message',
            'conversation_id' => $this->conversation->id,
            'message' => 'New message: '.\Illuminate\Support\Str::limit($this->message->body, 60),
            'url' => $url,
        ];
    }
}
