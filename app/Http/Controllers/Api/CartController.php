<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CartResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        $cart = CartResolver::findOrCreate($request);
        $product = Product::findOrFail($data['product_id']);
        $variant = $data['product_variant_id'] ? ProductVariant::find($data['product_variant_id']) : null;

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
        $this->authorizeItem($request, $item);

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
        $this->authorizeItem($request, $item);
        $item->delete();

        return response()->json(['items' => $this->itemsFor($request)]);
    }

    private function authorizeItem(Request $request, CartItem $item): void
    {
        $cart = CartResolver::current($request);
        abort_unless($cart && $item->cart_id === $cart->id, 403);
    }

    private function itemsFor(Request $request): array
    {
        $cart = CartResolver::current($request);

        if (! $cart) {
            return [];
        }

        return $cart->load(['items.product.images', 'items.variant'])
            ->items->map(fn (CartItem $item) => [
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