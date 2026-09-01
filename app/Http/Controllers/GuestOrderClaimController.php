<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GuestOrderClaimController extends Controller
{
    /**
     * Lets someone who just checked out as a guest turn that order
     * into a real account, without ever having been forced to create
     * one before completing their purchase.
     */
    public function store(Request $request, string $orderNumber): RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->whereNull('user_id')->firstOrFail();

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::firstOrCreate(
            ['email' => $order->guest_email],
            [
                'name' => $order->guest_name ?? 'Customer',
                'password' => Hash::make($data['password']),
                'role' => 'customer',
                'status' => 'active',
            ],
        );

        if (! $user->wasRecentlyCreated) {
            return back()->with('error', 'An account with this email already exists — please log in instead.');
        }

        event(new Registered($user));

        // Attach every guest order made with this same email, not just this one.
        Order::whereNull('user_id')->where('guest_email', $order->guest_email)->update(['user_id' => $user->id]);

        Auth::login($user);

        return to_route('customer.dashboard')->with('success', 'Account created — your orders are now saved to your account.');
    }
}