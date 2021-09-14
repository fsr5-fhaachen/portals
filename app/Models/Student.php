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

    /**
     * Returns Collection containing all students of specified timeslot or all if none provided.
     *
     * @param string $timeslotId Timeslot to select students by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByTimeslot($timeslotId = '')
    {
        if (empty($timeslotId)) return self::all();
        return self::where('timeslot_id', 'LIKE', $timeslotId)->get();
    }

    /**
     * Returns Collection containing all students of specified timeslot and course by first
     * selecting for timeslot and then selecting that result for the course.
     *
     * @param string $timeslotId Timeslot to select students by. Selects all if none provided
     * @param string $course Course to select students by. Selects all of previous result if none provided
     *
     * @return Collection
     */
    static function getByTimeslotAndCourse($timeslotId = '', $course = '')
    {
        $res=null;
        if (empty($timeslotId)) $res = self::all();
        else $res = self::where('timeslot_id', 'LIKE', $timeslotId);
        if (empty($course))  return $res->get();
        return $res->where('student_course', 'LIKE', $course)->get();
    }
}
