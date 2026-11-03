<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'abbreviation' => $this->faker->lexify('????'),
            'classes' => 'bg-gray-500 text-white',
            'icon' => $this->faker->word(),
        ];
    }
}
