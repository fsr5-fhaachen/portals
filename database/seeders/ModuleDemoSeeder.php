<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'registration',
                'active' => true,
                'expose_public' => false,
            ],
            [
                'key' => 'randomGenerator',
                'active' => true,
                'expose_public' => false,
            ],
            [
                'key' => 'scoreSystem',
                'active' => true,
                'expose_public' => false,
            ],
            [
                'key' => 'countdown',
                'active' => true,
                'expose_public' => false,
            ],
        ];

        foreach ($modules as $module) {
            // check if module with key already exists
            $existingModule = Module::where('key', $module['key'])->first();
            if (! $existingModule) {
                throw new \Exception('Module with key "' . $module['key'] . '" not found.');
            }

            // update module
            $existingModule->active = $module['active'];
            $existingModule->expose_public = $module['expose_public'];

            // save module
            $existingModule->save();
        }
    }
}
