<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grouphasstation extends Model
{
    use HasFactory;

    protected $table = 'groupHasStation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'station_id',
        'step',
        'done'
    ];
}
