<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\WorkshopAssignment;
use App\Models\WorkshopSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopAssignment>
 */
class WorkshopAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'workshop_session_id' => WorkshopSession::factory(),
            'workshop_block_id' => fn (array $attributes) => WorkshopSession::find($attributes['workshop_session_id'])->workshop_block_id,
        ];
    }
}
