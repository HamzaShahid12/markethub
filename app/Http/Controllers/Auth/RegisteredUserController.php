<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces Breeze's default RegisteredUserController so a single form
 * can create either a customer or a pending vendor. This is the file
 * you overwrite in `app/Http/Controllers/Auth/` after `breeze:install`.
 */
class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'status' => 'active',
            ]);

            if ($data['role'] === 'vendor') {
                Vendor::create([
                    'user_id' => $user->id,
                    'shop_name' => $data['shop_name'],
                    'slug' => $this->uniqueSlug($data['shop_name']),
                    'description' => $data['shop_description'] ?? null,
                    'address' => $data['shop_address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }

    private function uniqueSlug(string $shopName): string
    {
        $base = Str::slug($shopName);
        $slug = $base;
        $i = 1;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = "{$base}-".(++$i);
        }

        return $slug;
    }
}
