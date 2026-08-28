<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorCommissionFactory extends Factory
{
    public function definition(): array
    {
        $orderAmount = fake()->randomFloat(2, 15, 300);
        $rate = fake()->randomElement([8, 10, 12, 15]);
        $commission = round($orderAmount * ($rate / 100), 2);

        return [
            'vendor_id' => Vendor::factory(),
            'order_id' => Order::factory(),
            'order_item_id' => OrderItem::factory(),
            'order_amount' => $orderAmount,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'vendor_amount' => $orderAmount - $commission,
            'status' => 'paid',
        ];
    }
}
