<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grouphasstation extends Model
{
    use HasFactory;

    protected $table = 'grouphasstation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'id',
        'groupHasstep'
    ];
}
