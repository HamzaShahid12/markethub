<?php

namespace App\Actions\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;

/**
 * Single place that creates a chat message, bumps the conversation's
 * last_message_at, broadcasts it live to the open thread, and notifies
 * the other party in case they're not currently looking at it.
 */
class SendMessage
{
    public function execute(Conversation $conversation, User $sender, string $body): Message
    {
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender');
        broadcast(new MessageSent($message))->toOthers();

        $recipient = $sender->id === $conversation->customer_id
            ? $conversation->vendor->user
            : $conversation->customer;

        $recipient?->notify(new NewMessageNotification($conversation, $message));

        return $message;
    }
}
