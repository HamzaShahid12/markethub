<?php

use App\Actions\Orders\CreateOrder;
use App\Models\Cart;
use App\Models\User;
use App\Models\VendorCommission;

test('commission is calculated correctly from the vendor\'s commission rate', function () {
    $customer = User::factory()->create();
    $vendor = vendorWithUser();
    $vendor->update(['commission_rate' => 12]);
    $product = publishedProduct($vendor, ['price' => 50, 'stock' => 10]);

    $cart = Cart::firstOrCreate(['user_id' => $customer->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 50]);

    $order = (new CreateOrder)->execute($customer, [
        'name' => 'Test', 'line1' => 'x', 'city' => 'x', 'state' => 'x',
        'postal_code' => 'x', 'country' => 'x', 'phone' => 'x',
    ], null, 'cod');

    $commission = VendorCommission::where('order_id', $order->id)->first();

    // Line subtotal = 50 * 2 = 100; 12% of 100 = 12.00
    expect($commission)->not->toBeNull()
        ->and((float) $commission->order_amount)->toBe(100.0)
        ->and((float) $commission->commission_rate)->toBe(12.0)
        ->and((float) $commission->commission_amount)->toBe(12.0)
        ->and((float) $commission->vendor_amount)->toBe(88.0)
        ->and($commission->status)->toBe('pending');
});

test('a multi-vendor order creates one commission row per vendor', function () {
    $customer = User::factory()->create();
    $vendorA = vendorWithUser();
    $vendorB = vendorWithUser();
    $productA = publishedProduct($vendorA, ['price' => 20, 'stock' => 5]);
    $productB = publishedProduct($vendorB, ['price' => 30, 'stock' => 5]);

    $cart = Cart::firstOrCreate(['user_id' => $customer->id]);
    $cart->items()->create(['product_id' => $productA->id, 'quantity' => 1, 'price' => 20]);
    $cart->items()->create(['product_id' => $productB->id, 'quantity' => 1, 'price' => 30]);

    $order = (new CreateOrder)->execute($customer, [
        'name' => 'Test', 'line1' => 'x', 'city' => 'x', 'state' => 'x',
        'postal_code' => 'x', 'country' => 'x', 'phone' => 'x',
    ], null, 'cod');

    expect(VendorCommission::where('order_id', $order->id)->count())->toBe(2);
});

test('cancelling an order removes its vendor commissions and restores stock', function () {
    $customer = User::factory()->create();
    $vendor = vendorWithUser();
    $product = publishedProduct($vendor, ['price' => 40, 'stock' => 10]);

    $cart = Cart::firstOrCreate(['user_id' => $customer->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 40]);

    $order = (new CreateOrder)->execute($customer, [
        'name' => 'Test', 'line1' => 'x', 'city' => 'x', 'state' => 'x',
        'postal_code' => 'x', 'country' => 'x', 'phone' => 'x',
    ], null, 'cod');

    expect($product->fresh()->stock)->toBe(7);
    expect(VendorCommission::where('order_id', $order->id)->count())->toBe(1);

    (new \App\Actions\Orders\CancelOrder)->execute($order);

    expect($product->fresh()->stock)->toBe(10)
        ->and(VendorCommission::where('order_id', $order->id)->count())->toBe(0)
        ->and($order->fresh()->status)->toBe('cancelled');
});
