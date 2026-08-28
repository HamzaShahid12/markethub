<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

test('a customer can view their own order', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->get("/customer/orders/{$order->id}");

    $response->assertOk();
});

test('a customer cannot view another customer\'s order', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($intruder)->get("/customer/orders/{$order->id}");

    $response->assertForbidden();
});

test('a vendor can view an order containing their own item', function () {
    $vendor = vendorWithUser();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendor->id]);

    $response = $this->actingAs($vendor->user)->get("/vendor/orders/{$order->id}");

    $response->assertOk();
});

test('a vendor cannot view an order with none of their items', function () {
    $vendorA = vendorWithUser();
    $vendorB = vendorWithUser();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendorA->id]);

    $response = $this->actingAs($vendorB->user)->get("/vendor/orders/{$order->id}");

    $response->assertForbidden();
});

test('a vendor viewing a shared multi-vendor order only sees their own line items', function () {
    $vendorA = vendorWithUser();
    $vendorB = vendorWithUser();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendorA->id, 'product_name' => 'Vendor A Item']);
    OrderItem::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendorB->id, 'product_name' => 'Vendor B Item']);

    $response = $this->actingAs($vendorA->user)->get("/vendor/orders/{$order->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('order.items', 1)
        ->where('order.items.0.product_name', 'Vendor A Item')
    );
});

test('a vendor cannot update the status of another vendor\'s item', function () {
    $vendorA = vendorWithUser();
    $vendorB = vendorWithUser();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $itemB = OrderItem::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendorB->id, 'status' => 'pending']);

    $response = $this->actingAs($vendorA->user)
        ->put("/vendor/orders/{$order->id}/items/{$itemB->id}/status", ['status' => 'shipped']);

    $response->assertForbidden();
    expect($itemB->fresh()->status)->toBe('pending');
});

test('an admin can view any order', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($admin)->get("/admin/orders/{$order->id}");

    $response->assertOk();
});

test('a customer can cancel their own pending order and stock is restored', function () {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 5]);
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'pending']);
    OrderItem::factory()->create([
        'order_id' => $order->id, 'vendor_id' => $product->vendor_id,
        'product_id' => $product->id, 'quantity' => 2, 'status' => 'pending',
    ]);

    $response = $this->actingAs($customer)->post("/customer/orders/{$order->id}/cancel");

    $response->assertRedirect();
    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(7);
});

test('a customer cannot cancel another customer\'s order', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

    $response = $this->actingAs($intruder)->post("/customer/orders/{$order->id}/cancel");

    $response->assertForbidden();
});
