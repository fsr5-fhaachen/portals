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
            'student_firstname' => $this->faker->firstName(),
            'student_lastname' => $this->faker->lastName(),
            'student_email' => $this->faker->unique()->safeEmail(),
            'student_course' => $this->faker->regexify("(ET|INF|MCD|WI)"),
            'group_id' => null,
            'timeslot_id' => null,
            'student_attended' => 0
        ];
    }

    public function randAttendance()
    {
        return $this->state(function (array $attributes) {
            return [
                'student_attended' => $this->faker->regexify("[0-1]"),
            ];
        });
    }
}
