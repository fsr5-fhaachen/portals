<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_firstname',
        'student_lastname',
        'student_email',
        'student_course',
        'group_id',
        'timeslot_id'
    ];

    /**
     * Returns Collection containing all students of specified course or all if none provided.
     *
     * @param string $course Course to select students by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByCourse($course = '')
    {
        if (empty($course)) return self::all();
        return self::where('student_course', 'LIKE', $course)->get();
    }

    /**
     * Returns Collection containing all students of specified attendance or all if none provided.
     *
     * @param string $attendance Attendance to select students by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByAttendance($attendance = '')
    {
        if (empty($attendance)) return self::all();
        return self::where('student_attended', '=', $attendance)->get();
    }
}
