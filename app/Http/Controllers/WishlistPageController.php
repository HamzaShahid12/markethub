<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders /wishlist. Mutations go through Api\WishlistController and
 * the `wishlist` Pinia store, same pattern as CartController.
 */
class WishlistPageController extends Controller
{
    public function index(Request $request): Response
    {
        $wishlist = Wishlist::with('items.product.images')->firstWhere('user_id', $request->user()->id);

        $items = $wishlist
            ? $wishlist->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'price' => $item->product->price,
                'sale_price' => $item->product->sale_price,
                'image' => $item->product->images->first()?->image
                    ? asset('storage/'.$item->product->images->first()->image)
                    : null,
            ])->values()
            : [];

        return Inertia::render('Storefront/Wishlist', ['items' => $items]);
    }
}
