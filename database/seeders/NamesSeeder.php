<?php

namespace Database\Seeders;

use App\Models\GroupName;
use Illuminate\Database\Seeder;

class NamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvFile = fopen(base_path('names.csv'), 'r');
        $isFirstLine = true;
        while (($data = fgetcsv($csvFile, 2000, ';')) !== FALSE) {
            if ($isFirstLine) {
                $isFirstLine = false;
            } else {
                GroupName::create([
                    'id' => $data[0],
                    'name' => $data[1],
                ]);
            }
        }
        fclose($csvFile);
    }
}
