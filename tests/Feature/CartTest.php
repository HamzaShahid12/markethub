<?php

use App\Models\Cart;
use App\Models\User;

test('a guest cannot access the cart API', function () {
    $response = $this->getJson('/api/cart');

    $response->assertUnauthorized();
});

test('a customer can add a product to their cart', function () {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['price' => 25, 'stock' => 10]);

    $response = $this->actingAs($customer)->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertOk();
    $items = $response->json('items');
    expect($items)->toHaveCount(1)
        ->and($items[0]['quantity'])->toBe(2)
        ->and((float) $items[0]['price'])->toBe(25.0);
});

test('adding the same product twice increments quantity instead of duplicating the row', function () {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 10]);

    $this->actingAs($customer)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
    $response = $this->actingAs($customer)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

    $items = $response->json('items');
    expect($items)->toHaveCount(1)
        ->and($items[0]['quantity'])->toBe(3);
});

test('adding more than available stock is rejected', function () {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 2]);

    $response = $this->actingAs($customer)->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Only 2 left in stock.');
});

test('a customer can update a cart item quantity', function () {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 10]);

    $this->actingAs($customer)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
    $cart = Cart::where('user_id', $customer->id)->first();
    $item = $cart->items()->first();

    $response = $this->actingAs($customer)->putJson("/api/cart/items/{$item->id}", ['quantity' => 4]);

    $response->assertOk();
    expect($response->json('items.0.quantity'))->toBe(4);
});

test('a customer can remove a cart item', function () {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 10]);

    $this->actingAs($customer)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
    $cart = Cart::where('user_id', $customer->id)->first();
    $item = $cart->items()->first();

    $response = $this->actingAs($customer)->deleteJson("/api/cart/items/{$item->id}");

    $response->assertOk();
    expect($response->json('items'))->toHaveCount(0);
});

test('a customer cannot modify another customer\'s cart item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 10]);

    $this->actingAs($owner)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
    $item = Cart::where('user_id', $owner->id)->first()->items()->first();

    $response = $this->actingAs($intruder)->putJson("/api/cart/items/{$item->id}", ['quantity' => 9]);

    $response->assertForbidden();
});
