<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleErstiwocheSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = [
            [
                'key' => 'registration',
                'active' => true,
            ],
        ];

        foreach ($modules as $module) {
            // check if module with key already exists
            $existingModule = Module::where('key', $module['key'])->first();
            if ($existingModule) {
                continue;
            }

            // create a new module
            $newModule = new Module();
            $newModule->key = $module['key'];
            $newModule->active = $module['active'];

            // save module
            $newModule->save();
        }
    }
}