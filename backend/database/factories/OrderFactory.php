<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'order_number' => 'HOYOS-' . fake()->unique()->numerify('######'),
            'guest_token' => Str::random(32),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('01########'),
            'shipping_address' => fake()->streetAddress(),
            'province' => 'Phnom Penh',
            'delivery_notes' => null,
            'subtotal' => 58.00,
            'shipping_fee' => 0.00,
            'total_amount' => 58.00,
            'payment_method' => 'aba_khqr',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ];
    }
}
