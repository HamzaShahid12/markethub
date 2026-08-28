<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Low-stock visibility + quick stock adjustment (section 3.2:
 * "Inventory and low-stock management"). Kept separate from the full
 * product edit form so a vendor can fix a stock count in one click
 * without opening the whole product form.
 */
class InventoryController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $products = $vendor->products()
            ->with(['variants.attributeValues.attribute'])
            ->when($request->boolean('low_stock_only'), function ($q) {
                $q->where(function ($q) {
                    $q->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
                        ->orWhereHas('variants', fn ($q) => $q->where('stock', '<=', self::LOW_STOCK_THRESHOLD));
                });
            })
            ->orderBy('stock')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock,
                'low_stock' => $p->stock <= self::LOW_STOCK_THRESHOLD,
                'variants' => $p->variants->map(fn (ProductVariant $v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'stock' => $v->stock,
                    'low_stock' => $v->stock <= self::LOW_STOCK_THRESHOLD,
                    'label' => $v->attributeValues->pluck('value')->join(' / '),
                ]),
            ]);

        return Inertia::render('Vendor/Inventory/Index', [
            'products' => $products,
            'filters' => ['low_stock_only' => $request->boolean('low_stock_only')],
            'threshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate(['stock' => ['required', 'integer', 'min:0']]);
        $product->update($data);

        return back()->with('success', 'Stock updated.');
    }

    public function updateVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $variant->product);

        $data = $request->validate(['stock' => ['required', 'integer', 'min:0']]);
        $variant->update($data);

        return back()->with('success', 'Stock updated.');
    }
}
