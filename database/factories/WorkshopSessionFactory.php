<?php

namespace Database\Factories;

use App\Models\Workshop;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopSession>
 */
class WorkshopSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'workshop_block_id' => WorkshopBlock::factory(),
            'location' => 'Raum '.$this->faker->numberBetween(100, 999),
            'minimum_participants' => 0,
            'maximum_participants' => null,
            'is_cancelled' => false,
        ];
    }
}
