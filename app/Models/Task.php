<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get event for the task.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get completions for the task.
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * Get groups for the task.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'task_completions')->using(TaskCompletion::class);
    }
}
