<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $reviews = $request->user()->reviews()
            ->with('product:id,name,slug')
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Reviews/Index', [
            'reviews' => $reviews->through(fn (Review $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'status' => $r->status,
                'product_name' => $r->product?->name,
                'product_slug' => $r->product?->slug,
                'created_at' => $r->created_at->toDateString(),
            ]),
        ]);
    }

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