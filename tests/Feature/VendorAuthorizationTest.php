<?php

use App\Models\Category;
use App\Models\Product;

test('a pending vendor cannot see the create-product page', function () {
    $vendor = vendorWithUser(approved: false);

    $response = $this->actingAs($vendor->user)->get('/vendor/products/create');

    $response->assertForbidden();
});

test('a pending vendor cannot create a product', function () {
    $vendor = vendorWithUser(approved: false);
    $category = Category::factory()->create();

    $response = $this->actingAs($vendor->user)->post('/vendor/products', [
        'category_id' => $category->id,
        'name' => 'Should Not Save',
        'sku' => 'PENDING-SKU-1',
        'price' => 19.99,
        'stock' => 5,
        'status' => 'draft',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('products', ['sku' => 'PENDING-SKU-1']);
});

test('an approved vendor can create a product', function () {
    $vendor = vendorWithUser(approved: true);
    $category = Category::factory()->create();

    $response = $this->actingAs($vendor->user)->post('/vendor/products', [
        'category_id' => $category->id,
        'name' => 'New Product',
        'sku' => 'APPROVED-SKU-1',
        'price' => 29.99,
        'stock' => 10,
        'status' => 'draft',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', ['sku' => 'APPROVED-SKU-1', 'vendor_id' => $vendor->id]);
});

test('a vendor cannot edit another vendor\'s product', function () {
    $ownerVendor = vendorWithUser();
    $otherVendor = vendorWithUser();
    $product = publishedProduct($ownerVendor);

    $response = $this->actingAs($otherVendor->user)->get("/vendor/products/{$product->id}/edit");

    $response->assertForbidden();
});

test('a vendor cannot update another vendor\'s product', function () {
    $ownerVendor = vendorWithUser();
    $otherVendor = vendorWithUser();
    $product = publishedProduct($ownerVendor, ['name' => 'Original Name']);

    $response = $this->actingAs($otherVendor->user)->put("/vendor/products/{$product->id}", [
        'category_id' => $product->category_id,
        'name' => 'Hijacked Name',
        'sku' => $product->sku,
        'price' => $product->price,
        'stock' => $product->stock,
        'status' => 'published',
    ]);

    $response->assertForbidden();
    expect($product->fresh()->name)->toBe('Original Name');
});

test('a vendor cannot delete another vendor\'s product', function () {
    $ownerVendor = vendorWithUser();
    $otherVendor = vendorWithUser();
    $product = publishedProduct($ownerVendor);

    $response = $this->actingAs($otherVendor->user)->delete("/vendor/products/{$product->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('a customer cannot access vendor product management routes', function () {
    $customer = \App\Models\User::factory()->create();

    $response = $this->actingAs($customer)->get('/vendor/products');

    $response->assertForbidden();
});
