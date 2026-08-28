<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $price = fake()->randomFloat(2, 15, 300);

        return [
            'order_id' => Order::factory(),
            'vendor_id' => Vendor::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('??-#####')),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $price * $quantity,
            'status' => 'delivered',
        ];
    }
}
