<?php

use App\Models\User;

test('guests are rejected from every session-authenticated API endpoint', function () {
    $this->getJson('/api/cart')->assertUnauthorized();
    $this->getJson('/api/wishlist')->assertUnauthorized();
    $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
    $this->postJson('/api/coupons/validate', ['code' => 'X', 'subtotal' => 10])->assertUnauthorized();
});

test('an authenticated customer can reach their own wishlist and cart endpoints', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->getJson('/api/cart')->assertOk();
    $this->actingAs($customer)->getJson('/api/wishlist')->assertOk();
    $this->actingAs($customer)->getJson('/api/notifications/unread-count')->assertOk();
});

test('toggling a wishlist item requires authentication', function () {
    $product = publishedProduct();

    $this->postJson('/api/wishlist/toggle', ['product_id' => $product->id])->assertUnauthorized();
});

test('toggling a wishlist item adds then removes it', function () {
    $customer = User::factory()->create();
    $product = publishedProduct();

    $add = $this->actingAs($customer)->postJson('/api/wishlist/toggle', ['product_id' => $product->id]);
    $add->assertOk();
    expect($add->json('wishlisted'))->toBeTrue();
    expect($add->json('items'))->toHaveCount(1);

    $remove = $this->actingAs($customer)->postJson('/api/wishlist/toggle', ['product_id' => $product->id]);
    expect($remove->json('wishlisted'))->toBeFalse();
    expect($remove->json('items'))->toHaveCount(0);
});
