<?php

use App\Models\Coupon;
use App\Models\User;

test('a valid coupon within its rules is accepted with the correct discount', function () {
    $customer = User::factory()->create();
    Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'minimum_amount' => 50,
        'maximum_discount' => 100,
        'status' => 'active',
    ]);

    $response = $this->actingAs($customer)->postJson('/api/coupons/validate', [
        'code' => 'save10', // lowercase — should still match
        'subtotal' => 100,
    ]);

    $response->assertOk();
    expect((float) $response->json('discount'))->toBe(10.0);
});

test('a coupon below its minimum order amount is rejected', function () {
    $customer = User::factory()->create();
    Coupon::factory()->create(['code' => 'BIGSPEND', 'minimum_amount' => 200, 'status' => 'active']);

    $response = $this->actingAs($customer)->postJson('/api/coupons/validate', [
        'code' => 'BIGSPEND',
        'subtotal' => 50,
    ]);

    $response->assertStatus(422);
});

test('an expired coupon is rejected', function () {
    $customer = User::factory()->create();
    Coupon::factory()->create([
        'code' => 'EXPIRED1',
        'status' => 'active',
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($customer)->postJson('/api/coupons/validate', [
        'code' => 'EXPIRED1',
        'subtotal' => 100,
    ]);

    $response->assertStatus(422);
});

test('a coupon that has hit its usage limit is rejected', function () {
    $customer = User::factory()->create();
    Coupon::factory()->create([
        'code' => 'MAXEDOUT',
        'status' => 'active',
        'usage_limit' => 1,
        'used_count' => 1,
    ]);

    $response = $this->actingAs($customer)->postJson('/api/coupons/validate', [
        'code' => 'MAXEDOUT',
        'subtotal' => 100,
    ]);

    $response->assertStatus(422);
});

test('a percentage discount is capped by maximum_discount', function () {
    $customer = User::factory()->create();
    Coupon::factory()->create([
        'code' => 'CAPPED',
        'type' => 'percentage',
        'value' => 50,
        'maximum_discount' => 20,
        'minimum_amount' => null,
        'status' => 'active',
    ]);

    $response = $this->actingAs($customer)->postJson('/api/coupons/validate', [
        'code' => 'CAPPED',
        'subtotal' => 200, // 50% would be $100, capped to $20
    ]);

    $response->assertOk();
    expect((float) $response->json('discount'))->toBe(20.0);
});

test('an inactive coupon is rejected', function () {
    $customer = User::factory()->create();
    Coupon::factory()->create(['code' => 'OFFSWITCH', 'status' => 'inactive']);

    $response = $this->actingAs($customer)->postJson('/api/coupons/validate', [
        'code' => 'OFFSWITCH',
        'subtotal' => 100,
    ]);

    $response->assertStatus(422);
});
