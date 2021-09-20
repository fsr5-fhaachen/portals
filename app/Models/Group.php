<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'course',
        'station_id',
        'timeslot_id'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['title'];

    /**
     * Returns the title of the group.
     *
     * @return string
     */
    public function getTitleAttribute()
    {
        return "Gruppe {$this->id}";
    }

    /**
     * Returns Collection containing all groups of specified course or all if none provided.
     *
     * @param string $course Course to select groups by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByCourse($course = '')
    {
        if (empty($course)) return self::all();
        return self::where('course', 'LIKE', $course)->get();
    }

    /**
     * Returns Collection containing all groups of specified timeslot or all if none provided.
     *
     * @param string $timeslotId Timeslot to select groups by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByTimeslot($timeslotId = '')
    {
        if (empty($timeslotId)) return self::all();
        return self::where('timeslot_id', 'LIKE', $timeslotId)->get();
    }

    /**
     * Returns Collection containing all groups of specified station or all if none provided.
     *
     * @param string $stationId Station to select groups by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByStation($stationId = '')
    {
        if (empty($stationId)) return self::all();
        return self::where('station_id', 'LIKE', $stationId)->get();
    }

    /**
     * Returns Collection containing all groups of specified timeslot and course by first
     * selecting for timeslot and then selecting that result for the course.
     *
     * @param string $timeslotId Timeslot to select groups by. Selects all if none provided
     * @param string $course Course to select groups by. Selects all of previous result if none provided
     *
     * @return Collection
     */
    static function getByTimeslotAndCourse($timeslotId = '', $course = '')
    {
        $res = null;
        if (empty($timeslotId)) $res = self::all();
        else $res = self::where('timeslot_id', 'LIKE', $timeslotId);
        if (empty($course))  return $res->get();
        return $res->where('course', 'LIKE', $course)->get();
    }
}
