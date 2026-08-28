<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Single `/dashboard` entry point (Breeze's default post-login redirect
 * target) that routes each role to its own dashboard. Keeping this as
 * one small redirector — rather than teaching Breeze's login controller
 * about roles — means Breeze's auth files stay untouched and upgradeable.
 */
class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match ($user->role) {
            'admin' => to_route('admin.dashboard'),
            'vendor' => to_route('vendor.dashboard'),
            default => to_route('customer.dashboard'),
        };
    }
}
