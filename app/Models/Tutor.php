<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    use HasFactory;

    protected $table = 'tutors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'course',
        'group_id',
        'station_id'
    ];

    /**
     * Returns Collection containing all tutors of specified course or all if none provided.
     *
     * @param string $course Course to select tutors by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByCourse($course = '')
    {
        if (empty($course)) return self::all();
        return self::where('course', 'LIKE', $course)->get();
    }

    /**
     * Returns Collection containing all tutors of specified availability or all if none provided.
     *
     * @param string $availability Availability to select tutors by. Selects all if none provided
     *
     * @return Collection
     */
    static function getByAvailability($availability = '')
    {
        if (empty($availability)) return self::all();
        return self::where('tutor_available', '=', $availability)->get();
    }
}
