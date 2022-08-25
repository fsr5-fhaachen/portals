<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Stop extends Pivot
{
    use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get group for the stop.
   *
   * @return BelongsTo
   */
  public function group()
  {
    return $this->belongsTo(Group::class);
  }

  /**
   * Get station for the stop.
   *
   * @return BelongsTo
   */
  public function station()
  {
    return $this->belongsTo(Station::class);
  }
}
