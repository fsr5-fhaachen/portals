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
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'abbreviation' => $this->faker->lexify('????'),
            'classes' => $this->faker->randomElement([
                'bg-yellow-500 text-white dark:bg-yellow-600',
                'bg-blue-700 text-white dark:bg-blue-800',
                'bg-violet-800 text-white dark:bg-violet-900',
                'bg-fuchsia-700 text-white dark:bg-fuchsia-800',
                'bg-green-800 text-white dark:bg-green-900',
                'bg-stone-800 text-white dark:bg-stone-900',
                'bg-orange-400 text-white dark:bg-orange-500',
                'bg-cyan-700 text-white dark:bg-cyan-800',
                'bg-blue-300 text-white dark:bg-blue-400',
                'bg-red-500 text-white dark:bg-red-600'
            ]),
            'icon' => $this->faker->word(),
        ];
    }
}
