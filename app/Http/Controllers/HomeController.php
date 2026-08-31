<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $banners = Banner::currentlyLive()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Banner $b) => [
                'id' => $b->id,
                'eyebrow' => $b->eyebrow,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
                'image' => asset('storage/'.$b->image),
                'ctaLabel' => $b->cta_label,
                'ctaHref' => $b->cta_href,
            ]);

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->take(8)
            ->get(['id', 'name', 'slug', 'image'])
            ->map(fn (Category $c) => [
        'id' => $c->id, 
        'name' => $c->name,
        'slug' => $c->slug,
        'image' => $c->image ? asset('storage/'.$c->image) : null,
    ]);

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
            'banners' => $banners,
            'categories' => $categories,
            'trending' => $trending,
            'flashDeals' => $flashDeals,
            'flashDealsEndAt' => $flashDeals->isNotEmpty() ? Carbon::now()->addHours(6)->toIso8601String() : null,
            'vendors' => $vendors,
            'categoryDisplayStyle' => Setting::get('category_display_style', 'circle'),
        ]);
    }

    private function transformProduct(Product $product): array
    {
        $images = $product->images;

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'rating_average' => $product->rating_average,
            'rating_count' => $product->rating_count,
            'vendor_name' => $product->vendor?->shop_name,
            'image' => $images->first()?->image ? asset('storage/'.$images->first()->image) : null,
            'secondary_image' => $images->skip(1)->first()?->image ? asset('storage/'.$images->skip(1)->first()->image) : null,
        ];
    }
}