<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Commission/payout breakdown (section 3.2: "Platform commission
 * calculation" + earnings dashboard). Reads the VendorCommission rows
 * created transactionally at checkout (Phase 4) and cleaned up on
 * cancellation (Phase 5) — this page never recomputes commissions,
 * only reports them.
 */
class EarningsController extends Controller
{
    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $commissions = $vendor->commissions()
            ->with('order:id,order_number,created_at')
            ->latest()
            ->paginate(15);

        return Inertia::render('Vendor/Earnings/Index', [
            'commissions' => $commissions->through(fn ($c) => [
                'id' => $c->id,
                'order_number' => $c->order->order_number,
                'order_amount' => $c->order_amount,
                'commission_rate' => $c->commission_rate,
                'commission_amount' => $c->commission_amount,
                'vendor_amount' => $c->vendor_amount,
                'status' => $c->status,
                'date' => $c->created_at->toDateString(),
            ]),
            'totals' => [
                'pending' => (float) $vendor->commissions()->where('status', 'pending')->sum('vendor_amount'),
                'payable' => (float) $vendor->commissions()->where('status', 'payable')->sum('vendor_amount'),
                'paid' => (float) $vendor->commissions()->where('status', 'paid')->sum('vendor_amount'),
                'lifetime' => (float) $vendor->commissions()->sum('vendor_amount'),
            ],
            'commissionRate' => (float) $vendor->commission_rate,
        ]);
    }
}
