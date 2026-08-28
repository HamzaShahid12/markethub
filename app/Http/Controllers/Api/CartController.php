<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Server-backed cart (section 14: "Cart state should synchronize with
 * the server"). Every response returns the full current cart so the
 * Pinia store can just replace its state wholesale — no client-side
 * diffing or trusting stale local totals.
 */
class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['items' => $this->itemsFor($request)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $product = \App\Models\Product::findOrFail($data['product_id']);
        $variant = $data['product_variant_id'] ? \App\Models\ProductVariant::find($data['product_variant_id']) : null;

        $price = $variant?->price ?? $product->currentPrice();
        $availableStock = $variant ? $variant->stock : $product->stock;

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];

        if ($newQuantity > $availableStock) {
            return response()->json([
                'message' => "Only {$availableStock} left in stock.",
                'items' => $this->itemsFor($request),
            ], 422);
        }

        $item->quantity = $newQuantity;
        $item->price = $price;
        $item->cart_id = $cart->id;
        $item->save();

        return response()->json(['items' => $this->itemsFor($request)]);
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        abort_unless($item->cart->user_id === $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $availableStock = $item->variant?->stock ?? $item->product->stock;

        if ($data['quantity'] > $availableStock) {
            return response()->json([
                'message' => "Only {$availableStock} left in stock.",
                'items' => $this->itemsFor($request),
            ], 422);
        }

        $item->update(['quantity' => $data['quantity']]);

        return response()->json(['items' => $this->itemsFor($request)]);
    }

    public function destroy(Request $request, CartItem $item): JsonResponse
    {
        abort_unless($item->cart->user_id === $request->user()->id, 403);

        $item->delete();

        return response()->json(['items' => $this->itemsFor($request)]);
    }

    private function itemsFor(Request $request): array
    {
        $cart = Cart::with(['items.product.images', 'items.variant'])
            ->firstWhere('user_id', $request->user()->id);

        if (! $cart) {
            return [];
        }

        return $cart->items->map(fn (CartItem $item) => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'name' => $item->product->name,
            'slug' => $item->product->slug,
            'image' => $item->product->images->first()?->image
                ? asset('storage/'.$item->product->images->first()->image)
                : null,
            'variant_label' => $item->variant?->sku,
            'price' => (float) $item->price,
            'quantity' => $item->quantity,
            'stock' => $item->variant?->stock ?? $item->product->stock,
        ])->values()->all();
    }
}
