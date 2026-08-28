<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Every Feature test gets a fresh, migrated database per test (Phase 1's
| migrations + factories) via RefreshDatabase — no shared state between
| tests, no reliance on the seeder's demo data.
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Shared test helpers
|--------------------------------------------------------------------------
| Small factories-of-factories used across multiple test files, so each
| test stays focused on the behavior it's checking rather than the setup
| needed to reach it.
*/

/**
 * An approved (or, if $approved is false, pending) vendor with its own
 * backing user account. Use $vendor->user for the User model.
 */
function vendorWithUser(bool $approved = true): \App\Models\Vendor
{
    return $approved
        ? \App\Models\Vendor::factory()->create()
        : \App\Models\Vendor::factory()->pending()->create();
}

/**
 * A published product belonging to the given (or a fresh approved)
 * vendor, in the given (or a fresh) category.
 */
function publishedProduct(?\App\Models\Vendor $vendor = null, array $attrs = []): \App\Models\Product
{
    $vendor ??= vendorWithUser();

    return \App\Models\Product::factory()->create([
        'vendor_id' => $vendor->id,
        'status' => 'published',
        ...$attrs,
    ]);
}
