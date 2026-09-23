<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class TaskCompletion extends Pivot implements Auditable
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
     * Name of the table the model needs to link to. Needed because pivot naming conventions expect table to be in singular.
     *
     * @var string
     */
    protected $table = 'task_completions';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Get task for the task_completion.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get group for the task_completion.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who completed the task.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
