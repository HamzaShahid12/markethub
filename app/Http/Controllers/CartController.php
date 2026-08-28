<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the /cart page. Actual mutations (add/update/remove) go
 * through Api\CartController and the `cart` Pinia store — this
 * controller only needs to hand the store its initial state so the
 * page isn't empty on first paint before the client-side fetch runs.
 */
class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $cart = Cart::with(['items.product.images', 'items.variant'])->firstWhere('user_id', $request->user()?->id);

        $items = $cart
            ? $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'variant_label' => $item->variant?->sku,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'stock' => $item->variant?->stock ?? $item->product->stock,
                'image' => $item->product->images->first()?->image
                    ? asset('storage/'.$item->product->images->first()->image)
                    : null,
            ])->values()
            : [];

        return Inertia::render('Storefront/Cart', ['items' => $items]);
    }
}
