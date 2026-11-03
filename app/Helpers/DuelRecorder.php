<?php

namespace App\Helpers;

use App\Models\Competition;
use App\Models\Duel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DuelRecorder
{
    /**
     * Get the validation rules for the shape of a duel.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'team_a_id' => ['required', 'integer'],
            'team_b_id' => ['required', 'integer'],
            'result' => ['required', 'string', Rule::in(Duel::RESULTS)],
            'bonus_a' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bonus_b' => ['nullable', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Check the duel against the rules of the competition.
     *
     * @param  array{team_a_id: int, team_b_id: int, bonus_a?: int|null, bonus_b?: int|null}  $data
     * @return string|null German error message, or null if the duel is valid
     */
    public static function validationError(Competition $competition, array $data): ?string
    {
        $teamIds = [(int) $data['team_a_id'], (int) $data['team_b_id']];

        if ($teamIds[0] === $teamIds[1]) {
            return 'Ein Team kann nicht gegen sich selbst antreten.';
        }

        if ($competition->teams()->whereIn('id', $teamIds)->count() !== 2) {
            return 'Beide Teams müssen zum Wettbewerb gehören.';
        }

        $bonusA = (int) ($data['bonus_a'] ?? 0);
        $bonusB = (int) ($data['bonus_b'] ?? 0);
        if ($bonusA > $competition->maxBonusFor($bonusB) || $bonusB > $competition->maxBonusFor($bonusA)) {
            return $competition->bonus_per_team
                ? 'Jedes Team darf höchstens '.$competition->bonus_pool.' Bonuspunkte bekommen.'
                : 'Der Bonus darf zusammen höchstens '.$competition->bonus_pool.' Punkte betragen.';
        }

        return null;
    }

    /**
     * Create or update the duel and remember who did it.
     *
     * @param  array{team_a_id: int, team_b_id: int, result: string, bonus_a?: int|null, bonus_b?: int|null, note?: string|null}  $data
     */
    public static function save(Duel $duel, Competition $competition, array $data, User $actor): Duel
    {
        $note = trim((string) ($data['note'] ?? ''));

        $duel->fill([
            'competition_id' => $competition->id,
            'team_a_id' => (int) $data['team_a_id'],
            'team_b_id' => (int) $data['team_b_id'],
            'result' => $data['result'],
            'bonus_a' => (int) ($data['bonus_a'] ?? 0),
            'bonus_b' => (int) ($data['bonus_b'] ?? 0),
            'note' => $note !== '' ? $note : null,
        ]);

        if (! $duel->exists) {
            $duel->created_by = $actor->id;
        } elseif ($duel->isDirty()) {
            $duel->updated_by = $actor->id;
        }

        $duel->save();

        return $duel;
    }

    /**
     * Validate the duel of the current request against the competition.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validate(Competition $competition): array
    {
        $data = Request::validate(self::rules());

        $error = self::validationError($competition, $data);
        if ($error) {
            throw ValidationException::withMessages(['duel' => $error]);
        }

        return $data;
    }

    /**
     * Get the duels of the competition for display, newest first.
     *
     * @return Collection<int, Duel>
     */
    public static function listFor(Competition $competition): Collection
    {
        return $competition->duels()
            ->with([
                'teamA:id,name',
                'teamB:id,name',
                'creator:id,firstname,lastname',
                'updater:id,firstname,lastname',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Describe the duel for flash messages, e.g. "<strong>Gruppe 1</strong> vs. <strong>Gruppe 2</strong>".
     */
    public static function describe(Duel $duel): string
    {
        $duel->loadMissing(['teamA:id,name', 'teamB:id,name']);

        return '<strong>'.e($duel->teamA?->name).'</strong> vs. <strong>'.e($duel->teamB?->name).'</strong>';
    }
}
