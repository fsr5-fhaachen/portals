<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'registration',
                'active' => false,
                'expose_public' => false,
            ],
            [
                'key' => 'randomGenerator',
                'active' => false,
                'expose_public' => false,
            ],
            [
                'key' => 'scoreSystem',
                'active' => false,
                'expose_public' => false,
            ],
            [
                'key' => 'countdown',
                'active' => false,
                'expose_public' => false,
            ],
        ];

        foreach ($modules as $module) {
            // check if module with key already exists
            $existingModule = Module::where('key', $module['key'])->first();
            if ($existingModule) {
                continue;
            }

            // create a new module
            $newModule = new Module;
            $newModule->key = $module['key'];
            $newModule->active = $module['active'];
            $newModule->expose_public = $module['expose_public'];

            // save module
            $newModule->save();
        }
    }
}
