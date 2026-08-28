<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function update(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || ($user->isVendor() && $user->vendor?->id === $vendor->id);
    }

    public function approve(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin();
    }

    public function manageOrders(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || ($user->isVendor() && $user->vendor?->id === $vendor->id);
    }
}
