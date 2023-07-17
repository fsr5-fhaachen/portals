<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupTutor>
 */
class GroupTutorFactory extends Factory
{
    /**
     * Define the model's default state. Expects that the users and groups tables are already pre-populated.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::all(['id'])->random(),
            'group_id' => Group::all(['id'])->random(),
        ];
    }
}
