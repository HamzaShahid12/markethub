<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 30, 600);

        return [
            'user_id' => User::factory(),
            'order_number' => 'MH-'.strtoupper(Str::random(8)),
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping_fee' => 10,
            'total' => $subtotal + 10,
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered']),
            'payment_status' => 'paid',
            'payment_method' => fake()->randomElement(['card', 'cod', 'paypal']),
            'shipping_address' => [
                'name' => fake()->name(),
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->country(),
                'phone' => fake()->phoneNumber(),
            ],
        ];
    }
}
