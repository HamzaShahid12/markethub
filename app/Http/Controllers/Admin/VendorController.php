<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin vendor management + approval workflow (section 3.2/3.3).
 * Authorization for each action is delegated to VendorPolicy (Phase 1),
 * not re-implemented here — this controller only orchestrates.
 */
class VendorController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $vendors = Vendor::query()
            ->with('user:id,name,email')
            ->withCount('products')
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => ['status' => $status ?: 'all'],
            'counts' => [
                'all' => Vendor::count(),
                'pending' => Vendor::where('status', 'pending')->count(),
                'approved' => Vendor::where('status', 'approved')->count(),
                'suspended' => Vendor::where('status', 'suspended')->count(),
                'rejected' => Vendor::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function approve(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);

        $vendor->update(['status' => 'approved', 'approved_at' => now()]);

        return back()->with('success', "{$vendor->shop_name} has been approved.");
    }

    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);

        $vendor->update(['status' => 'rejected', 'approved_at' => null]);

        return back()->with('success', "{$vendor->shop_name} has been rejected.");
    }

    public function suspend(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);

        $vendor->update(['status' => 'suspended']);

        return back()->with('success', "{$vendor->shop_name} has been suspended.");
    }
}
