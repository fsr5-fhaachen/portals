<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

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
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'course' => $this->faker->regexify("(ET|INF|MCD|WI)"),
            'id' => null,
            'id' => null,
            'attended' => 0
        ];
    }

    public function randAttendance()
    {
        return $this->state(function (array $attributes) {
            return [
                'attended' => $this->faker->regexify("[0-1]"),
            ];
        });
    }
}
