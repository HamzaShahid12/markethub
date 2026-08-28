<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public storefront product listing + details (section 3.1).
 * Listing supports search, category/price/rating/vendor/availability
 * filters, and featured/newest/price/rating/popularity sorting.
 */
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Storefront/Products/Index', $this->buildListing($request));
    }

    /**
     * Shared by CategoryController@show so the filter/sort UI isn't
     * duplicated for "all products" vs. "products in category X".
     */
    public function buildListing(Request $request, ?Category $lockedCategory = null): array
    {
        $sort = $request->string('sort')->toString() ?: 'newest';

        $products = Product::query()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order'), 'vendor:id,shop_name'])
            ->where('status', 'published')
            ->when($lockedCategory, fn ($q) => $q->where('category_id', $lockedCategory->id))
            ->when(
                ! $lockedCategory && $request->integer('category_id'),
                fn ($q) => $q->where('category_id', $request->integer('category_id'))
            )
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('vendor_id'), fn ($q, $vendorId) => $q->where('vendor_id', $vendorId))
            ->when($request->float('min_price'), fn ($q, $min) => $q->where('price', '>=', $min))
            ->when($request->float('max_price'), fn ($q, $max) => $q->where('price', '<=', $max))
            ->when($request->float('min_rating'), fn ($q, $min) => $q->where('rating_average', '>=', $min))
            ->when($request->boolean('in_stock_only'), fn ($q) => $q->where('stock', '>', 0))
            ->when($request->boolean('on_sale'), fn ($q) => $q->whereNotNull('sale_price'))
            ->when($sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
            ->when($sort === 'price_low', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($sort === 'price_high', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when($sort === 'rating', fn ($q) => $q->orderByDesc('rating_average'))
            ->when($sort === 'popularity', fn ($q) => $q->orderByDesc('sold_count'))
            ->when($sort === 'featured', fn ($q) => $q->orderByDesc('featured')->orderByDesc('published_at'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'price' => $p->price,
                'sale_price' => $p->sale_price,
                'rating_average' => $p->rating_average,
                'rating_count' => $p->rating_count,
                'vendor_name' => $p->vendor?->shop_name,
                'image' => $p->images->first()?->image ? asset('storage/'.$p->images->first()->image) : null,
            ]);

        return [
            'products' => $products,
            'filters' => $request->only([
                'search', 'category_id', 'vendor_id', 'min_price', 'max_price',
                'min_rating', 'in_stock_only', 'on_sale', 'sort',
            ]),
            'categories' => Category::whereNull('parent_id')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'slug']),
            'vendors' => Vendor::where('status', 'approved')->orderBy('shop_name')->get(['id', 'shop_name']),
            'lockedCategory' => $lockedCategory?->only(['id', 'name', 'slug']),
        ];
    }

    public function show(Product $product): Response
    {
        abort_unless($product->status === 'published', 404);

        $product->load([
            'images',
            'vendor:id,shop_name,slug,rating_average',
            'category:id,name,slug',
            'variants.attributeValues.attribute',
            'approvedReviews.user:id,name',
        ]);

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'published')
            ->with(['images', 'vendor:id,shop_name'])
            ->take(4)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'price' => $p->price,
                'sale_price' => $p->sale_price,
                'rating_average' => $p->rating_average,
                'rating_count' => $p->rating_count,
                'vendor_name' => $p->vendor?->shop_name,
                'image' => $p->images->first()?->image ? asset('storage/'.$p->images->first()->image) : null,
            ]);

        return Inertia::render('Storefront/Products/Show', [
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'stock' => $product->stock,
                'rating_average' => $product->rating_average,
                'rating_count' => $product->rating_count,
                'images' => $product->images->map(fn ($i) => asset('storage/'.$i->image)),
                'vendor' => $product->vendor,
                'category' => $product->category,
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'stock' => $v->stock,
                    'attribute_values' => $v->attributeValues->map(fn ($av) => [
                        'attribute' => $av->attribute->name,
                        'value' => $av->value,
                    ]),
                ]),
                'reviews' => $product->approvedReviews->map(fn ($r) => [
                    'id' => $r->id,
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'user_name' => $r->user->name,
                    'created_at' => $r->created_at->toDateString(),
                ]),
            ],
            'related' => $related,
        ]);
    }
}
