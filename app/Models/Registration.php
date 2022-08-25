<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get event for the registration.
   *
   * @return BelongsTo
   */
  public function event()
  {
    return $this->belongsTo(Event::class);
  }

  /**
   * Get user for the registration.
   *
   * @return BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get slot for the registration.
   *
   * @return BelongsTo
   */
  public function slot()
  {
    return $this->belongsTo(Slot::class);
  }

  /**
   * Get group for the registration.
   *
   * @return BelongsTo
   */
  public function group()
  {
    return $this->belongsTo(Group::class);
  }
}
