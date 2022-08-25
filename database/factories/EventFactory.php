<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
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
          'registration_from' => $this->faker->dateTimeBetween('now', '+2 hours'),
          'registration_until' => $this->faker->dateTimeBetween('+2 hours', '+2 days'),
          'type' => $this->faker->randomElement(['group_phase', 'event_registration', 'slot_booking']),
          'has_requirements' => $this->faker->boolean(),
          'consider_alcohol' => $this->faker->boolean(),
          'form' => '{}'
        ];
    }
}
