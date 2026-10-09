<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->numerify('01########'),
            'province' => 'Phnom Penh',
            'address_line' => fake()->streetAddress(),
            'notes' => null,
            'is_default' => true,
        ];
    }
}
