<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\ExtraPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraPoint>
 */
class ExtraPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'competition_team_id' => fn (array $attributes) => CompetitionTeam::factory()->state(['competition_id' => $attributes['competition_id']]),
            'points' => $this->faker->numberBetween(1, 5),
            'reason' => 'Bingo',
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the extra points are a penalty.
     */
    public function penalty(): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => -$this->faker->numberBetween(1, 5),
            'reason' => 'Strafe',
        ]);
    }
}
