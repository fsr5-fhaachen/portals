<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Duel extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public const RESULT_TEAM_A = 'team_a';

    public const RESULT_DRAW = 'draw';

    public const RESULT_TEAM_B = 'team_b';

    public const RESULTS = [
        self::RESULT_TEAM_A,
        self::RESULT_DRAW,
        self::RESULT_TEAM_B,
    ];

    public const OUTCOME_WIN = 'win';

    public const OUTCOME_DRAW = 'draw';

    public const OUTCOME_LOSS = 'loss';

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
            'team_a_id' => 'integer',
            'team_b_id' => 'integer',
            'bonus_a' => 'integer',
            'bonus_b' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * Get the competition of the duel.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get team A of the duel.
     */
    public function teamA(): BelongsTo
    {
        return $this->belongsTo(CompetitionTeam::class, 'team_a_id');
    }

    /**
     * Get team B of the duel.
     */
    public function teamB(): BelongsTo
    {
        return $this->belongsTo(CompetitionTeam::class, 'team_b_id');
    }

    /**
     * Get the user who recorded the duel.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last changed the duel.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the outcome (win, draw, loss) of the duel for team A.
     */
    public function outcomeForTeamA(): string
    {
        return match ($this->result) {
            self::RESULT_TEAM_A => self::OUTCOME_WIN,
            self::RESULT_DRAW => self::OUTCOME_DRAW,
            default => self::OUTCOME_LOSS,
        };
    }

    /**
     * Get the outcome (win, draw, loss) of the duel for team B.
     */
    public function outcomeForTeamB(): string
    {
        return match ($this->result) {
            self::RESULT_TEAM_B => self::OUTCOME_WIN,
            self::RESULT_DRAW => self::OUTCOME_DRAW,
            default => self::OUTCOME_LOSS,
        };
    }
}
