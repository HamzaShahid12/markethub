<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Customers may only view their own orders. Vendors may view an order
     * only if it contains at least one of their order items. Admins see all.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return $order->user_id === $user->id;
        }

        if ($user->isVendor()) {
            return $order->items()->where('vendor_id', $user->vendor?->id)->exists();
        }

        return false;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $order->items()->where('vendor_id', $user->vendor?->id)->exists();
        }

        return false;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin() || ($user->isCustomer() && $order->user_id === $user->id);
    }
}
