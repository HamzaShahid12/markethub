<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cross-vendor product moderation (section 3.3: "Category and product
 * moderation"). Admin can see and hide any product but doesn't edit
 * its content directly — that stays with the owning vendor
 * (ProductPolicy from Phase 1: only the vendor or admin can mutate,
 * and here we deliberately only expose a status toggle, not the full
 * edit form, to keep vendor content ownership clear).
 */
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with(['vendor:id,shop_name', 'category:id,name'])
            ->when($request->string('status')->toString(), fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q)
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $request->string('status')->toString() ?: 'all',
                'category_id' => $request->integer('category_id') ?: null,
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $product->update($data);

        return back()->with('success', "\"{$product->name}\" is now {$data['status']}.");
    }
}
