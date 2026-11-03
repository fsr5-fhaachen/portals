<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The pin of all created users.
     *
     * @var string
     */
    public const PIN = '4711';

    /**
     * The hashed pin, hashing it for every user would slow down the seeders and tests.
     */
    protected static ?string $hashedPin = null;

    /**
     * Define the model's default state. Expects that the courses table is already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'course_id' => Course::all(['id'])->random(),
            'pin' => static::$hashedPin ??= Hash::make(self::PIN),
        ];
    }

    /**
     * Indicate that the user has not set a pin yet.
     */
    public function withoutPin(): static
    {
        return $this->state(fn (array $attributes) => [
            'pin' => null,
        ]);
    }
}
