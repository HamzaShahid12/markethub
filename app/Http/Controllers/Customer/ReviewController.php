<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

/**
 * "A customer can review a product only after purchasing it" (section
 * 6.3) — enforced by ReviewPolicy::create (Phase 1), which checks for
 * a delivered order containing this product and blocks a second
 * review of the same product/order pair. New reviews land as
 * `pending` and go through the admin moderation queue from Phase 7
 * before they affect the product's public rating.
 */
class ReviewController extends Controller
{
    public function store(ReviewRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        $this->authorize('create', [Review::class, $product]);

        Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'order_id' => $data['order_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Thanks — your review is awaiting moderation.');
    }
}
