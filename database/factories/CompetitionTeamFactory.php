<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionTeam>
 */
class CompetitionTeamFactory extends Factory
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
            'group_id' => null,
            'name' => $this->faker->unique()->words(2, true),
        ];
    }

    /**
     * Link the team to a group and take over its name.
     */
    public function forGroup(Group $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => $group->id,
            'name' => $group->name,
        ]);
    }
}
