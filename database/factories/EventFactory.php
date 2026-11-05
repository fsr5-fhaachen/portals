<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $registration_from = $this->faker->dateTimeBetween('-2 days', '+2 days');

        return [
            'name' => $this->faker->word(),
            'registration_from' => $registration_from,
            'registration_to' => $this->faker->dateTimeBetween($registration_from, '+2 days'),
            'type' => $this->faker->randomElement(['group_phase', 'event_registration', 'slot_booking']),
            'has_requirements' => $this->faker->boolean(),
            'consider_alcohol' => $this->faker->boolean(),
            'sort_order' => $this->faker->numberBetween(0, 255),
        ];
    }

    /**
     * Indicate that the event is a workshop booking with the given amount of priorities.
     */
    public function workshopBooking(int $priorityCount = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'workshop_booking',
            'has_requirements' => false,
            'consider_alcohol' => false,
            'workshop_priority_count' => $priorityCount,
        ]);
    }
}
