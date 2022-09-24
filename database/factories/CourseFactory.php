<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
          'name' => $this->faker->word(),
          'abbreviation' => $this->faker->lexify('????'),
          'color' => $this->faker->word(),
          'icon' => $this->faker->word()
        ];
    }
}
