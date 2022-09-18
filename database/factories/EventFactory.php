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
        $registration_from = $this->faker->dateTimeBetween('-2 days', '+2 days');

        return [
          'name' => $this->faker->word(),
          'registration_from' => $registration_from,
          'registration_to' => $this->faker->dateTimeBetween($registration_from, '+2 days'),
          'type' => $this->faker->randomElement(['group_phase', 'event_registration', 'slot_booking']),
          'has_requirements' => $this->faker->boolean(),
          'consider_alcohol' => $this->faker->boolean(),
          'sort_order' => $this->faker->random_int(0, 255),
        ];
    }
}
