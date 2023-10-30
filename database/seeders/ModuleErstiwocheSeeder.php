<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleErstiwocheSeeder extends Seeder
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
            ],
        ];

        foreach ($modules as $module) {
            // check if module with key already exists
            $existingModule = Module::where('key', $module['key'])->first();
            if (! $existingModule) {
                throw new \Exception('Module with key "'.$module['key'].'" not found.');
            }

            // update module
            $existingModule->active = $module['active'];

            // save module
            $existingModule->save();
        }
    }
}
