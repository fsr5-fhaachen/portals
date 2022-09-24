<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupName extends Model
{
    protected $table = 'groupName';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
    ];
}
