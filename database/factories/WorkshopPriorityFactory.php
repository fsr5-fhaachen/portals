<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\Workshop;
use App\Models\WorkshopPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopPriority>
 */
class WorkshopPriorityFactory extends Factory
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
            'workshop_id' => Workshop::factory(),
            'priority' => 1,
        ];
    }
}
