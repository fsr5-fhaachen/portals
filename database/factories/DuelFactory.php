<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Duel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Duel>
 */
class DuelFactory extends Factory
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
            'team_a_id' => fn (array $attributes) => CompetitionTeam::factory()->state(['competition_id' => $attributes['competition_id']]),
            'team_b_id' => fn (array $attributes) => CompetitionTeam::factory()->state(['competition_id' => $attributes['competition_id']]),
            'result' => $this->faker->randomElement(Duel::RESULTS),
            'bonus_a' => 0,
            'bonus_b' => 0,
            'note' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that team A won the duel.
     */
    public function teamAWins(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => Duel::RESULT_TEAM_A,
        ]);
    }

    /**
     * Indicate that the duel ended in a draw.
     */
    public function draw(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => Duel::RESULT_DRAW,
        ]);
    }

    /**
     * Indicate that team B won the duel.
     */
    public function teamBWins(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => Duel::RESULT_TEAM_B,
        ]);
    }

    /**
     * Set the bonus points of both teams.
     */
    public function withBonus(int $bonusA, int $bonusB): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_a' => $bonusA,
            'bonus_b' => $bonusB,
        ]);
    }

    /**
     * Let the given teams compete in the duel.
     */
    public function between(CompetitionTeam $teamA, CompetitionTeam $teamB): static
    {
        return $this->state(fn (array $attributes) => [
            'competition_id' => $teamA->competition_id,
            'team_a_id' => $teamA->id,
            'team_b_id' => $teamB->id,
        ]);
    }
}
