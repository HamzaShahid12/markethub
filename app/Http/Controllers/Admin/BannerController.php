<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        $banners = Banner::orderBy('sort_order')->get()->map(fn (Banner $b) => [
            'id' => $b->id,
            'eyebrow' => $b->eyebrow,
            'title' => $b->title,
            'subtitle' => $b->subtitle,
            'image' => asset('storage/'.$b->image),
            'cta_label' => $b->cta_label,
            'cta_href' => $b->cta_href,
            'sort_order' => $b->sort_order,
            'is_active' => $b->is_active,
            'starts_at' => $b->starts_at?->format('Y-m-d'),
            'ends_at' => $b->ends_at?->format('Y-m-d'),
        ]);

        return Inertia::render('Admin/Banners/Index', ['banners' => $banners]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image'] = $request->file('image')->store('banners', 'public');

        Banner::create($data);

        return back()->with('success', 'Banner created.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validated($request, $banner);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return back()->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }

    public function toggleActive(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('success', $banner->is_active ? 'Banner activated.' : 'Banner deactivated.');
    }

    private function validated(Request $request, ?Banner $banner = null): array
    {
        return $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => [$banner ? 'nullable' : 'required', 'image', 'max:4096'],
            'cta_label' => ['nullable', 'string', 'max:50'],
            'cta_href' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}