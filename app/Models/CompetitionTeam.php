<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class CompetitionTeam extends Model implements Auditable
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
            'competition_id' => 'integer',
            'group_id' => 'integer',
        ];
    }

    /**
     * Get the competition of the team.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the group the team was imported from.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get duels in which the team is team A.
     */
    public function duelsAsTeamA(): HasMany
    {
        return $this->hasMany(Duel::class, 'team_a_id');
    }

    /**
     * Get duels in which the team is team B.
     */
    public function duelsAsTeamB(): HasMany
    {
        return $this->hasMany(Duel::class, 'team_b_id');
    }

    /**
     * Get extra points for the team.
     */
    public function extraPoints(): HasMany
    {
        return $this->hasMany(ExtraPoint::class);
    }

    /**
     * Determine whether duels or extra points reference the team.
     */
    public function hasScoringEntries(): bool
    {
        return $this->duelsAsTeamA()->exists()
            || $this->duelsAsTeamB()->exists()
            || $this->extraPoints()->exists();
    }
}
