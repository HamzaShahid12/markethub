<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'type' => fake()->randomElement(['fixed', 'percentage']),
            'value' => fake()->randomElement([10, 15, 20, 25]),
            'minimum_amount' => 50,
            'maximum_discount' => 100,
            'usage_limit' => 100,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
        ];
    }
}
