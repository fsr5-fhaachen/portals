<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class WorkshopSession extends Model implements Auditable
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_participants' => 'integer',
            'maximum_participants' => 'integer',
            'is_cancelled' => 'boolean',
        ];
    }

    /**
     * Get workshop for the session.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /**
     * Get block for the session.
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(WorkshopBlock::class, 'workshop_block_id');
    }

    /**
     * Get the assignments of the users to the session.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(WorkshopAssignment::class);
    }
}
