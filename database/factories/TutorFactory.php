<?php

namespace Database\Factories;

use App\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

class TutorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tutor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            // created_at
            // updated_at
            'tutor_firstname' => $this->faker->firstName(),
            'tutor_lastname' => $this->faker->lastName(),
            'tutor_email' => $this->faker->unique()->safeEmail(),
            'tutor_course' => $this->faker->regexify("(ET|INF|MCD|WI)"),
            'group_id' => null,
            'station_id' => null,
        ];
    }
}
