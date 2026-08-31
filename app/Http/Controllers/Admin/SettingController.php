<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'category_display_style' => Setting::get('category_display_style', 'circle'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_display_style' => ['required', 'in:circle,card,square'],
        ]);

        Setting::set('category_display_style', $data['category_display_style']);

        return back()->with('success', 'Settings updated.');
    }
}