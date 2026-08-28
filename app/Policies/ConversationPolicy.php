<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isCustomer()) {
            return $conversation->customer_id === $user->id;
        }

        if ($user->isVendor()) {
            return $conversation->vendor_id === $user->vendor?->id;
        }

        return false;
    }
}
