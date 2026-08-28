<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Coupon validation (section 6.3: "Coupons must be validated for date,
 * usage, minimum amount and maximum discount"). All rules live on the
 * Coupon model itself (Phase 1) — this endpoint just applies them
 * against the caller's current cart subtotal.
 */
class CouponController extends Controller
{
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::whereRaw('upper(code) = ?', [strtoupper($data['code'])])->first();

        if (! $coupon || ! $coupon->isValidFor((float) $data['subtotal'])) {
            return response()->json(['message' => 'This coupon is invalid or cannot be applied to your order.'], 422);
        }

        return response()->json([
            'code' => $coupon->code,
            'discount' => $coupon->calculateDiscount((float) $data['subtotal']),
        ]);
    }
}
