<?php

namespace App\Support;

use App\Models\Cart;
use Illuminate\Http\Request;

/**
 * Resolves the current cart for either a logged-in user (by user_id)
 * or a guest (by browser session id) — so cart/checkout works fully
 * without forcing login, per the guest-checkout requirement.
 */
class CartResolver
{
    public static function current(Request $request): ?Cart
    {
        if ($request->user()) {
            return Cart::where('user_id', $request->user()->id)->first();
        }

        return Cart::where('session_id', $request->session()->getId())
            ->whereNull('user_id')
            ->first();
    }

    public static function findOrCreate(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id]);
        }

        return Cart::firstOrCreate(
            ['session_id' => $request->session()->getId(), 'user_id' => null],
        );
    }
}