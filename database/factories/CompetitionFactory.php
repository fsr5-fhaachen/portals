<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'event_id' => null,
            'points_win' => 4,
            'points_draw' => 2,
            'points_loss' => 0,
            'bonus_pool' => 2,
            'bonus_per_team' => true,
            'is_open' => true,
        ];
    }

    /**
     * Indicate that the competition is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => false,
        ]);
    }

    /**
     * Let both teams of a duel share one bonus pool instead of a limit per team.
     */
    public function sharedBonusPool(): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_per_team' => false,
        ]);
    }

    /**
     * Link the competition to an event.
     */
    public function forEvent(?Event $event = null): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event?->id ?? Event::factory(),
        ]);
    }

    /**
     * Create the given amount of teams for the competition.
     */
    public function withTeams(int $count = 2): static
    {
        return $this->afterCreating(function (Competition $competition) use ($count) {
            CompetitionTeam::factory()
                ->count($count)
                ->sequence(fn ($sequence) => ['name' => 'Team '.($sequence->index + 1)])
                ->for($competition)
                ->create();
        });
    }
}
