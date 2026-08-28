<?php

use App\Actions\Orders\CreateOrder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

function cartWithItem(User $customer, Product $product, int $quantity): CartItem
{
    $cart = Cart::firstOrCreate(['user_id' => $customer->id]);

    return $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => $quantity,
        'price' => $product->currentPrice(),
    ]);
}

$shippingAddress = fn () => [
    'name' => 'Jamie Customer',
    'line1' => '1 Market Street',
    'city' => 'Lahore',
    'state' => 'Punjab',
    'postal_code' => '54000',
    'country' => 'Pakistan',
    'phone' => '+92 300 0000000',
];

test('a successful checkout creates an order, decrements stock, and clears the cart', function () use ($shippingAddress) {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['price' => 40, 'stock' => 10]);
    cartWithItem($customer, $product, 2);

    $order = (new CreateOrder)->execute($customer, $shippingAddress(), null, 'cod');

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->items)->toHaveCount(1)
        ->and((float) $order->subtotal)->toBe(80.0)
        ->and($order->status)->toBe('pending');

    $product->refresh();
    expect($product->stock)->toBe(8)
        ->and($product->sold_count)->toBe(2);

    $this->assertDatabaseCount('cart_items', 0);
});

test('checkout fails and creates nothing when the cart has insufficient stock', function () use ($shippingAddress) {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['stock' => 1]);
    cartWithItem($customer, $product, 5);

    expect(fn () => (new CreateOrder)->execute($customer, $shippingAddress(), null, 'cod'))
        ->toThrow(RuntimeException::class);

    $this->assertDatabaseCount('orders', 0);
    expect($product->fresh()->stock)->toBe(1); // untouched
});

test('checkout fails on an empty cart', function () use ($shippingAddress) {
    $customer = User::factory()->create();

    expect(fn () => (new CreateOrder)->execute($customer, $shippingAddress(), null, 'cod'))
        ->toThrow(RuntimeException::class, 'Your cart is empty.');
});

test('a valid coupon reduces the order total and increments its used_count', function () use ($shippingAddress) {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['price' => 100, 'stock' => 10]);
    cartWithItem($customer, $product, 1);

    $coupon = \App\Models\Coupon::factory()->create([
        'code' => 'ORDER10',
        'type' => 'percentage',
        'value' => 10,
        'minimum_amount' => 50,
        'used_count' => 0,
    ]);

    $order = (new CreateOrder)->execute($customer, $shippingAddress(), 'ORDER10', 'cod');

    expect((float) $order->discount)->toBe(10.0)
        ->and((float) $order->total)->toBe(90.0 + (float) $order->shipping_fee);

    expect($coupon->fresh()->used_count)->toBe(1);
});

test('full checkout flow through the HTTP endpoint places a real order', function () use ($shippingAddress) {
    $customer = User::factory()->create();
    $product = publishedProduct(null, ['price' => 30, 'stock' => 5]);
    cartWithItem($customer, $product, 1);

    $response = $this->actingAs($customer)->post('/checkout', [
        ...$shippingAddress(),
        'payment_method' => 'cod',
    ]);

    $order = Order::where('user_id', $customer->id)->first();

    expect($order)->not->toBeNull();
    $response->assertRedirect("/orders/{$order->order_number}/success");
});
