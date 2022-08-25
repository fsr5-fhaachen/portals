<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupTutor extends Pivot
{
    use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get user for the group_tutor.
   *
   * @return BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get group for the group_tutor.
   *
   * @return BelongsTo
   */
  public function group()
  {
    return $this->belongsTo(Group::class);
  }
}
