<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Competition extends Model implements Auditable
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
            'event_id' => 'integer',
            'points_win' => 'integer',
            'points_draw' => 'integer',
            'points_loss' => 'integer',
            'bonus_pool' => 'integer',
            'bonus_per_team' => 'boolean',
            'is_open' => 'boolean',
        ];
    }

    /**
     * Get the event whose groups can be imported as teams.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get teams for the competition.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(CompetitionTeam::class);
    }

    /**
     * Get the teams in natural order, so "Gruppe 2" comes before "Gruppe 10".
     *
     * @param  list<string>  $columns
     * @param  list<string>  $counts  relations to count
     * @return Collection<int, CompetitionTeam>
     */
    public function teamsInNaturalOrder(array $columns = ['*'], array $counts = []): Collection
    {
        return $this->teams()
            ->select($columns)
            ->withCount($counts)
            ->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Get the highest valid bonus of a single team, given the bonus of the other team.
     */
    public function maxBonusFor(int $otherTeamBonus): int
    {
        return $this->bonus_per_team ? $this->bonus_pool : max(0, $this->bonus_pool - $otherTeamBonus);
    }

    /**
     * Get duels for the competition.
     */
    public function duels(): HasMany
    {
        return $this->hasMany(Duel::class);
    }

    /**
     * Get extra points for the competition.
     */
    public function extraPoints(): HasMany
    {
        return $this->hasMany(ExtraPoint::class);
    }

    /**
     * Get the points a team receives for the given duel outcome.
     */
    public function pointsFor(string $outcome): int
    {
        return match ($outcome) {
            Duel::OUTCOME_WIN => $this->points_win,
            Duel::OUTCOME_DRAW => $this->points_draw,
            default => $this->points_loss,
        };
    }
}
