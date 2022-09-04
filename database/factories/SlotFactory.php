<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Slot>
 */
class SlotFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the events table is already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
          'event_id' => Event::all(['id'])->random(),
          'has_requirements' => $this->faker->boolean(),
          'maximum_participants' => $this->faker->numberBetween(0, 25),
          'form' => '{}'
        ];
    }
}
