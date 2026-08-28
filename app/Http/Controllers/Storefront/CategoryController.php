<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->with(['children' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Storefront/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Reuses ProductController's listing builder so category browsing
     * gets the exact same search/filter/sort UI as /products.
     */
    public function show(Request $request, Category $category, ProductController $products): Response
    {
        abort_unless($category->status === 'active', 404);

        return Inertia::render('Storefront/Products/Index', $products->buildListing($request, $category));
    }
}
