<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $table = 'stations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['tutors'];

    /**
     * Returns the tutors of the group.
     *
     * @return string
     */
    public function getTutorsAttribute()
    {
        return Tutor::where('station_id', $this->id)->get();
    }
}
