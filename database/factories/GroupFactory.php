<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the events and courses tables are already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'event_id' => Event::all(['id'])->random(),
            'course_id' => Course::all(['id'])->random(),
        ];
    }
}
