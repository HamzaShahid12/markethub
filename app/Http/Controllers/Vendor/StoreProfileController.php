<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        return Inertia::render('Vendor/StoreProfile/Edit', [
            'vendor' => [
                'shop_name' => $vendor->shop_name,
                'description' => $vendor->description,
                'phone' => $vendor->phone,
                'address' => $vendor->address,
                'logo' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
                'banner' => $vendor->banner ? asset('storage/'.$vendor->banner) : null,
                'status' => $vendor->status,
                'commission_rate' => $vendor->commission_rate,
                'rating_average' => $vendor->rating_average,
            ],
        ]);
    }

    public function update(StoreProfileRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($vendor->logo) {
                Storage::disk('public')->delete($vendor->logo);
            }
            $data['logo'] = $request->file('logo')->store('vendors/logos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($vendor->banner) {
                Storage::disk('public')->delete($vendor->banner);
            }
            $data['banner'] = $request->file('banner')->store('vendors/banners', 'public');
        }

        $vendor->update($data);

        return back()->with('success', 'Store profile updated.');
    }
}
