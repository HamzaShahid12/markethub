<?php

use App\Models\Category;

test('a vendor can update their own product', function () {
    $vendor = vendorWithUser();
    $product = publishedProduct($vendor, ['name' => 'Old Name', 'price' => 10]);

    $response = $this->actingAs($vendor->user)->put("/vendor/products/{$product->id}", [
        'category_id' => $product->category_id,
        'name' => 'New Name',
        'sku' => $product->sku,
        'price' => 15,
        'stock' => $product->stock,
        'status' => 'published',
    ]);

    $response->assertRedirect();
    $product->refresh();
    expect($product->name)->toBe('New Name')
        ->and((float) $product->price)->toBe(15.0);
});

test('a vendor can delete their own product', function () {
    $vendor = vendorWithUser();
    $product = publishedProduct($vendor);

    $response = $this->actingAs($vendor->user)->delete("/vendor/products/{$product->id}");

    $response->assertRedirect('/vendor/products');
    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

test('product creation validates a unique sku', function () {
    $vendor = vendorWithUser();
    $category = Category::factory()->create();
    $existing = publishedProduct($vendor);

    $response = $this->actingAs($vendor->user)->post('/vendor/products', [
        'category_id' => $category->id,
        'name' => 'Duplicate SKU Product',
        'sku' => $existing->sku,
        'price' => 9.99,
        'stock' => 3,
        'status' => 'draft',
    ]);

    $response->assertSessionHasErrors('sku');
});

test('sale price must be less than the regular price', function () {
    $vendor = vendorWithUser();
    $category = Category::factory()->create();

    $response = $this->actingAs($vendor->user)->post('/vendor/products', [
        'category_id' => $category->id,
        'name' => 'Bad Sale Price',
        'sku' => 'BAD-SALE-1',
        'price' => 20,
        'sale_price' => 25,
        'stock' => 3,
        'status' => 'draft',
    ]);

    $response->assertSessionHasErrors('sale_price');
});

test('a published product is visible on the public storefront listing', function () {
    $vendor = vendorWithUser();
    publishedProduct($vendor, ['name' => 'Findable Product']);

    $response = $this->get('/products');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Storefront/Products/Index')
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Findable Product')
    );
});

test('a draft product does not appear on the public storefront listing', function () {
    $vendor = vendorWithUser();
    $draft = \App\Models\Product::factory()->create(['vendor_id' => $vendor->id, 'status' => 'draft']);

    $response = $this->get("/products/{$draft->slug}");

    $response->assertNotFound();
});
