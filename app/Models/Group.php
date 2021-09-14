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
        'group_name',
        'group_course',
        'station_id',
        'timeslot_id'
    ];

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
        return self::where('group_course', 'LIKE', $course)->get();
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
}
