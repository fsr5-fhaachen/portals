<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the events and courses tables are already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'event_id' => Event::all(['id'])->random(),
        ];
    }

    /**
     * Configure the model factory. The course of a group is stored in the pivot table course_group.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Group $group) {
            $group->courses()->attach(Course::all(['id'])->random());
        });
    }
}
