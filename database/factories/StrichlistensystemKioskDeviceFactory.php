<?php

namespace Database\Factories;

use App\Models\StrichlistensystemKioskDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StrichlistensystemKioskDevice>
 */
class StrichlistensystemKioskDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Tablet '.$this->faker->unique()->word(),
            'token' => StrichlistensystemKioskDevice::generateToken(),
            'last_used_at' => null,
        ];
    }
}
