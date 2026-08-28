<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels (Phase 9)
|--------------------------------------------------------------------------
| The default `App.Models.User.{id}` private channel Laravel uses for
| broadcast notifications is authorized automatically by the framework
| — nothing to register here for that. This file only needs the
| conversation channel, since only the two participants (the customer
| and that vendor's user account) may listen to a given thread.
|
| Register this file in bootstrap/app.php — see the README for the
| exact snippet.
*/

Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return $conversation->customer_id === $user->id
        || $conversation->vendor_id === $user->vendor?->id;
});
