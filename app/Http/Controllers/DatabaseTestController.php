<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;
use App\Models\Student;

class DatabaseTestController extends Controller
{

    // needs to be changed if database changes!
    private $tableNames = [
        'grouphasstation',
        'groups',
        'stations',
        'students',
        'timeslots',
        'tutors'
    ];

    public function clearAllTables()
    {
        foreach ($this->tableNames as $tableName){
            DB::table($tableName)->delete();
        }
    }

    public function clearTable($tableName)
    {
        DB::table($tableName)->delete();
    }

    public function randomFillTable($tableName, $amount)
    {
        if($tableName === 'students') return $this->randomFillStudents($amount);
        else return 'Table not found!';
    }

    public function randomFillStudents($amount)
    {
        $student = Student::factory()->count($amount)->create();
        return $student;
    }
}
