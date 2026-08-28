<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Storefront home page (section 3.1: hero, categories, trending,
     * flash deals, vendors, newsletter).
     */
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->take(8)
            ->get(['id', 'name', 'slug']);

        $trending = Product::query()
            ->with(['images', 'vendor:id,shop_name'])
            ->where('status', 'published')
            ->orderByDesc('sold_count')
            ->take(10)
            ->get()
            ->map(fn (Product $p) => $this->transformProduct($p));

        $flashDeals = Product::query()
            ->with(['images', 'vendor:id,shop_name'])
            ->where('status', 'published')
            ->whereNotNull('sale_price')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (Product $p) => $this->transformProduct($p));

        $vendors = Vendor::query()
            ->withCount('products')
            ->where('status', 'approved')
            ->orderByDesc('rating_average')
            ->take(8)
            ->get(['id', 'shop_name', 'slug']);

        return Inertia::render('Storefront/Home', [
            'categories' => $categories,
            'trending' => $trending,
            'flashDeals' => $flashDeals,
            'flashDealsEndAt' => $flashDeals->isNotEmpty() ? Carbon::now()->addHours(6)->toIso8601String() : null,
            'vendors' => $vendors,
        ]);
    }

    private function transformProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'rating_average' => $product->rating_average,
            'rating_count' => $product->rating_count,
            'vendor_name' => $product->vendor?->shop_name,
            'image' => $product->images->first()?->image
                ? asset('storage/'.$product->images->first()->image)
                : null,
        ];
    }
}
