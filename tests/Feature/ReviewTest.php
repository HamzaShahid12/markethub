<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;

function deliveredOrderFor(User $customer, \App\Models\Product $product): Order
{
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'delivered']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $product->vendor_id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'status' => 'delivered',
    ]);

    return $order;
}

test('a customer can review a product they purchased and received', function () {
    $customer = User::factory()->create();
    $product = publishedProduct();
    $order = deliveredOrderFor($customer, $product);

    $response = $this->actingAs($customer)->post('/customer/reviews', [
        'product_id' => $product->id,
        'order_id' => $order->id,
        'rating' => 5,
        'comment' => 'Loved it.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reviews', [
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'order_id' => $order->id,
        'status' => 'pending',
    ]);
});

test('a customer cannot review a product they never purchased', function () {
    $customer = User::factory()->create();
    $product = publishedProduct();
    $unrelatedOrder = Order::factory()->create(['user_id' => $customer->id, 'status' => 'delivered']);

    $response = $this->actingAs($customer)->post('/customer/reviews', [
        'product_id' => $product->id,
        'order_id' => $unrelatedOrder->id,
        'rating' => 5,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('reviews', 0);
});

test('a customer cannot review a product before it has been delivered', function () {
    $customer = User::factory()->create();
    $product = publishedProduct();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'processing']);
    OrderItem::factory()->create([
        'order_id' => $order->id, 'vendor_id' => $product->vendor_id,
        'product_id' => $product->id, 'status' => 'processing',
    ]);

    $response = $this->actingAs($customer)->post('/customer/reviews', [
        'product_id' => $product->id,
        'order_id' => $order->id,
        'rating' => 4,
    ]);

    $response->assertForbidden();
});

test('a customer cannot review the same product twice for the same order', function () {
    $customer = User::factory()->create();
    $product = publishedProduct();
    $order = deliveredOrderFor($customer, $product);

    Review::factory()->create([
        'user_id' => $customer->id, 'product_id' => $product->id, 'order_id' => $order->id,
    ]);

    $response = $this->actingAs($customer)->post('/customer/reviews', [
        'product_id' => $product->id,
        'order_id' => $order->id,
        'rating' => 3,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('reviews', 1);
});
