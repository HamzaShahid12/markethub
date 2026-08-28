<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString() ?: 'pending';

        $reviews = Review::query()
            ->with(['user:id,name', 'product:id,name,slug'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => ['status' => $status],
            'counts' => [
                'pending' => Review::where('status', 'pending')->count(),
                'approved' => Review::where('status', 'approved')->count(),
                'rejected' => Review::where('status', 'rejected')->count(),
                'all' => Review::count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', Review::class);

        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);

        $review->update($data);

        if ($data['status'] === 'approved') {
            $this->recalculateProductRating($review->product_id);
        }

        return back()->with('success', 'Review '.$data['status'].'.');
    }

    private function recalculateProductRating(int $productId): void
    {
        $product = \App\Models\Product::find($productId);
        if (! $product) {
            return;
        }

        $approved = $product->approvedReviews();

        $product->update([
            'rating_average' => round($approved->avg('rating') ?? 0, 2),
            'rating_count' => $approved->count(),
        ]);
    }
}
