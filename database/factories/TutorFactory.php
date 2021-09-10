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
            // group_id
            // station_id
            // timeslot_id
            'tutor_available' => 0
        ];
    }

    public function randAvailability()
    {
        return $this->state(function (array $attributes) {
            return [
                'tutor_available' => $this->faker->regexify("[0-1]"),
            ];
        });
    }
}
