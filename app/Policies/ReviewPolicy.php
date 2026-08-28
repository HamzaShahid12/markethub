<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class ReviewPolicy
{
    /**
     * A customer may only review a product they have actually received
     * (delivered order item), and only once per order.
     */
    public function create(User $user, Product $product): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        $hasDeliveredOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        if (! $hasDeliveredOrder) {
            return false;
        }

        $alreadyReviewed = $product->reviews()->where('user_id', $user->id)->exists();

        return ! $alreadyReviewed;
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }
}
