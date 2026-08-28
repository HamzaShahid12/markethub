<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 15, 500);
        $onSale = fake()->boolean(30);

        return [
            'vendor_id' => Vendor::factory(),
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'sku' => strtoupper(Str::random(3)).'-'.fake()->unique()->numberBetween(10000, 99999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'price' => $price,
            'sale_price' => $onSale ? round($price * 0.8, 2) : null,
            'stock' => fake()->numberBetween(0, 200),
            'weight' => fake()->randomFloat(2, 0.1, 10),
            'status' => 'published',
            'featured' => fake()->boolean(20),
            'rating_average' => fake()->randomFloat(2, 3, 5),
            'rating_count' => fake()->numberBetween(0, 250),
            'sold_count' => fake()->numberBetween(0, 500),
            'published_at' => now(),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
