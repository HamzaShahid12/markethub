<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorCommission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function index(Request $request): Response
    {
        $commissions = VendorCommission::query()
            ->with(['vendor:id,shop_name', 'order:id,order_number'])
            ->when($request->integer('vendor_id'), fn ($q, $id) => $q->where('vendor_id', $id))
            ->when($request->string('status')->toString(), fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Commissions/Index', [
            'commissions' => $commissions->through(fn (VendorCommission $c) => [
                'id' => $c->id,
                'vendor_name' => $c->vendor->shop_name,
                'order_number' => $c->order->order_number,
                'order_amount' => $c->order_amount,
                'commission_rate' => $c->commission_rate,
                'commission_amount' => $c->commission_amount,
                'vendor_amount' => $c->vendor_amount,
                'status' => $c->status,
                'date' => $c->created_at->toDateString(),
            ]),
            'vendors' => Vendor::where('status', 'approved')->orderBy('shop_name')->get(['id', 'shop_name']),
            'filters' => [
                'vendor_id' => $request->integer('vendor_id') ?: null,
                'status' => $request->string('status')->toString() ?: 'all',
            ],
            'totals' => [
                'platform_earned' => (float) VendorCommission::sum('commission_amount'),
                'vendors_earned' => (float) VendorCommission::sum('vendor_amount'),
                'pending' => (float) VendorCommission::where('status', 'pending')->sum('commission_amount'),
            ],
        ]);
    }
}
