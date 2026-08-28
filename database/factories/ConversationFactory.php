<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'last_message_at' => now(),
        ];
    }
}
