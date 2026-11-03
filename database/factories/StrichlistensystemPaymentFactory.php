<?php

namespace Database\Factories;

use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StrichlistensystemPayment>
 */
class StrichlistensystemPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'created_by_id' => null,
            'amount' => $this->faker->numberBetween(1, 5000),
        ];
    }
}
