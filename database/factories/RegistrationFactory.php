<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the events, users, slots and groups tables are already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
          'event_id' => Event::all(['id'])->random(),
          'user_id' => User::all(['id'])->random(),
          'slot_id' => Slot::all(['id'])->random(),
          'group_id' => Group::all(['id'])->random(),
          'drinks_alcohol' => $this->faker->boolean(),
          'fulfils_requirements' => $this->faker->boolean(),
          'form_responses' => '{}',
          'queue_position' => -1
        ];
    }
}
