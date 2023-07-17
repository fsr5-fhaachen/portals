<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class Stop extends Pivot implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Name of the table the model needs to link to. Needed because pivot naming conventions expect table to be in singular.
     *
     * @var string
     */
    protected $table = 'stops';

    /**
     * Get group for the stop.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get station for the stop.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
