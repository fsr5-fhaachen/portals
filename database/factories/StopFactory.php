<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stop>
 */
class StopFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the groups and stations tables are already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
          'group_id' => Group::all(['id'])->random(),
          'station_id' => Station::all(['id'])->random(),
          'arrival_at' => $this->faker->dateTimeBetween('now', '+2 hours'),
          'departure_at' => $this->faker->dateTimeBetween('+2 hours', '+3 hours')
        ];
    }
}
