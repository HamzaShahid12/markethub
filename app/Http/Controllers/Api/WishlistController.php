<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['items' => $this->itemsFor($request)]);
    }

    /**
     * Toggle: adds the product if it's not already wishlisted, removes
     * it if it is. Keeps the heart-icon toggle on ProductCard a single
     * request instead of needing to know client-side whether it's on.
     */
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $wishlist = Wishlist::firstOrCreate(['user_id' => $request->user()->id]);

        $existing = $wishlist->items()->where('product_id', $data['product_id'])->first();

        if ($existing) {
            $existing->delete();
        } else {
            $wishlist->items()->create(['product_id' => $data['product_id']]);
        }

        return response()->json(['items' => $this->itemsFor($request), 'wishlisted' => ! $existing]);
    }

    public function destroy(Request $request, WishlistItem $item): JsonResponse
    {
        abort_unless($item->wishlist->user_id === $request->user()->id, 403);

        $item->delete();

        return response()->json(['items' => $this->itemsFor($request)]);
    }

    private function itemsFor(Request $request): array
    {
        $wishlist = Wishlist::with('items.product.images')->firstWhere('user_id', $request->user()->id);

        if (! $wishlist) {
            return [];
        }

        return $wishlist->items->map(fn (WishlistItem $item) => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'slug' => $item->product->slug,
            'price' => $item->product->price,
            'sale_price' => $item->product->sale_price,
            'image' => $item->product->images->first()?->image
                ? asset('storage/'.$item->product->images->first()->image)
                : null,
        ])->values()->all();
    }
}
