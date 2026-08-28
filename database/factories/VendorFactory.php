<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        $shopName = fake()->unique()->company();

        return [
            'user_id' => User::factory()->vendor(),
            'shop_name' => $shopName,
            'slug' => Str::slug($shopName).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->paragraph(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => 'approved',
            'commission_rate' => fake()->randomElement([8, 10, 12, 15]),
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'approved_at' => null]);
    }
}
