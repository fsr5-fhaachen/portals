<?php

namespace Database\Factories;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StationTutor>
 */
class StationTutorFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the users and stations tables are already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::with('roles')->pluck('id')->random(),
            'station_id' => Station::all(['id'])->random(),
        ];
    }
}
