<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\WorkshopBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopBlock>
 */
class WorkshopBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory()->workshopBooking(),
            'name' => 'Block '.$this->faker->unique()->numberBetween(1, 1000),
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+3 days'),
            'ends_at' => null,
        ];
    }
}
