<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

test('a customer can register and lands on the customer dashboard', function () {
    $response = $this->post('/register', [
        'role' => 'customer',
        'name' => 'Jamie Customer',
        'email' => 'jamie@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'jamie@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('customer')
        ->and(Hash::check('password123', $user->password))->toBeTrue();

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('registering as a vendor creates a pending Vendor record in the same transaction', function () {
    $this->post('/register', [
        'role' => 'vendor',
        'name' => 'Alex Vendor',
        'email' => 'alex@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'shop_name' => 'Alex\'s Shop',
        'shop_address' => '123 Market St',
    ]);

    $user = User::where('email', 'alex@example.com')->first();
    $vendor = Vendor::where('user_id', $user->id)->first();

    expect($user->role)->toBe('vendor')
        ->and($vendor)->not->toBeNull()
        ->and($vendor->shop_name)->toBe('Alex\'s Shop')
        ->and($vendor->status)->toBe('pending');
});

test('vendor registration requires a shop name', function () {
    $response = $this->post('/register', [
        'role' => 'vendor',
        'name' => 'No Shop',
        'email' => 'noshop@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('shop_name');
    $this->assertDatabaseMissing('users', ['email' => 'noshop@example.com']);
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register', [
        'role' => 'customer',
        'name' => 'Duplicate',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});
