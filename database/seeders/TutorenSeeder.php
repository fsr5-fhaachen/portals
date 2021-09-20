<?php

namespace Database\Seeders;

use App\Models\Tutor;
use Illuminate\Database\Seeder;

class TutorenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvFile = fopen(base_path('tutoren.csv'), 'r');
        while (($data = fgetcsv($csvFile, 2000, ';')) !== FALSE) {
            Tutor::create([
                'lastname' => $data['0'],
                'firstname' => $data['1'],
                'email' => $data['2'],
            ]);
        }
        fclose($csvFile);
    }
}
