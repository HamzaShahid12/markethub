<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->withCount('orders')
            ->when($request->string('role')->toString(), fn ($q, $role) => $role !== 'all' ? $q->where('role', $role) : $q)
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'role' => $request->string('role')->toString() ?: 'all',
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    /**
     * Admin cannot suspend another admin (guards against locking
     * everyone out) and cannot suspend themselves.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role === 'admin', 403, 'Admin accounts cannot be suspended from here.');
        abort_if($user->id === $request->user()->id, 403, "You can't suspend your own account.");

        $user->update(['status' => $user->status === 'active' ? 'suspended' : 'active']);

        return back()->with('success', $user->status === 'active' ? 'User reactivated.' : 'User suspended.');
    }
}
