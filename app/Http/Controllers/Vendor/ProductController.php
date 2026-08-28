<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ProductRequest;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vendor-facing product CRUD (section 3.2). Every action is scoped to
 * the authenticated vendor's own products; ProductPolicy (Phase 1)
 * decides whether the vendor is allowed to create (must be approved)
 * or edit/delete (must own the product) — this controller just calls it.
 */
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $products = $vendor->products()
            ->with(['category:id,name', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->string('status')->toString(), fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Vendor/Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString() ?: 'all',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Vendor/Products/Create', $this->formProps());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $vendor = $request->user()->vendor;
        $data = $request->validated();

        $product = DB::transaction(function () use ($data, $vendor, $request) {
            $product = $vendor->products()->create([
                ...collect($data)->except(['images', 'removed_image_ids', 'variants'])->all(),
                'slug' => $this->uniqueSlug($data['name']),
                'published_at' => $data['status'] === 'published' ? now() : null,
            ]);

            $this->syncImages($product, $request);
            $this->syncVariants($product, $data['variants'] ?? []);

            return $product;
        });

        return to_route('vendor.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load(['images', 'variants.attributeValues.attribute']);

        return Inertia::render('Vendor/Products/Edit', [
            ...$this->formProps(),
            'product' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'stock' => $product->stock,
                'weight' => $product->weight,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'status' => $product->status,
                'images' => $product->images->map(fn ($i) => ['id' => $i->id, 'image' => $i->image]),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'stock' => $v->stock,
                    // Explicit snake_case shape so the frontend never depends
                    // on Eloquent's raw (camelCase) relation-name serialization.
                    'attribute_values' => $v->attributeValues->map(fn ($av) => [
                        'id' => $av->id,
                        'value' => $av->value,
                        'attribute' => ['id' => $av->attribute->id, 'name' => $av->attribute->name],
                    ]),
                ]),
            ],
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        DB::transaction(function () use ($data, $product, $request) {
            $product->update([
                ...collect($data)->except(['images', 'removed_image_ids', 'variants'])->all(),
                'slug' => $data['name'] !== $product->name ? $this->uniqueSlug($data['name'], $product->id) : $product->slug,
                'published_at' => $data['status'] === 'published' ? ($product->published_at ?? now()) : $product->published_at,
            ]);

            $this->syncImages($product, $request);
            $this->syncVariants($product, $data['variants'] ?? []);
        });

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return to_route('vendor.products.index')->with('success', 'Product removed.');
    }

    private function formProps(): array
    {
        return [
            'categories' => \App\Models\Category::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'attributes' => Attribute::with('values')->get(),
        ];
    }

    private function syncImages(Product $product, Request $request): void
    {
        foreach ($request->input('removed_image_ids', []) as $imageId) {
            $image = $product->images()->find($imageId);
            if ($image) {
                Storage::disk('public')->delete($image->image);
                $image->delete();
            }
        }

        $nextOrder = $product->images()->max('sort_order') + 1;

        foreach ($request->file('images', []) as $file) {
            $path = $file->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image' => $path,
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $keepIds = [];

        foreach ($variants as $variant) {
            $model = ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'sku' => $variant['sku']],
                [
                    'price' => $variant['price'] ?? null,
                    'stock' => $variant['stock'],
                ],
            );

            $model->attributeValues()->sync($variant['attribute_value_ids']);
            $keepIds[] = $model->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-".(++$i);
        }

        return $slug;
    }
}
