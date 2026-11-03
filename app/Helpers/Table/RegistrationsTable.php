<?php

namespace App\Helpers\Table;

use App\Models\Group;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registrations of an event, a slot or a group.
 */
class RegistrationsTable extends Table
{
    public function __construct(
        Request $request,
        protected ?int $eventId = null,
        protected ?int $slotId = null,
        protected ?int $groupId = null,
    ) {
        parent::__construct($request);
    }

    /**
     * Check if any registration of the table has form responses (to show the column).
     */
    public function hasFormResponses(): bool
    {
        return $this->query()->whereNotNull('registrations.form_responses')->exists();
    }

    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return Registration::query()
            ->with('user:id,firstname,lastname,email,course_id,updated_at')
            ->when($this->eventId !== null, fn (Builder $query) => $query->where('registrations.event_id', $this->eventId))
            ->when($this->slotId !== null, fn (Builder $query) => $query->where('registrations.slot_id', $this->slotId))
            ->when($this->groupId !== null, fn (Builder $query) => $query->where('registrations.group_id', $this->groupId));
    }

    /**
     * {@inheritDoc}
     *
     * @param  Registration  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'user_id' => $model->user_id,
            'event_id' => $model->event_id,
            'slot_id' => $model->slot_id,
            'group_id' => $model->group_id,
            'drinks_alcohol' => $model->drinks_alcohol,
            'is_present' => $model->is_present,
            'fulfils_requirements' => $model->fulfils_requirements,
            'queue_position' => $model->queue_position,
            'form_responses' => $model->form_responses,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
            'user' => $model->user?->only(['id', 'firstname', 'lastname', 'email', 'course_id']),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param  Registration  $model
     */
    protected function rowVersion(Model $model): int
    {
        return max((int) $model->updated_at?->timestamp, (int) $model->user?->updated_at?->timestamp);
    }

    /**
     * {@inheritDoc}
     */
    protected function whereChangedSince(Builder $query, Carbon $since): void
    {
        $query->where('registrations.updated_at', '>=', $since)
            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('users.updated_at', '>=', $since));
    }

    /**
     * {@inheritDoc}
     */
    protected function searchColumns(): array
    {
        return ['user.firstname', 'user.lastname', 'user.email'];
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'firstname' => fn (Builder $query, string $direction) => $query->orderBy(
                User::select('firstname')->whereColumn('users.id', 'registrations.user_id'),
                $direction,
            ),
            'lastname' => fn (Builder $query, string $direction) => $query->orderBy(
                User::select('lastname')->whereColumn('users.id', 'registrations.user_id'),
                $direction,
            ),
            'course' => fn (Builder $query, string $direction) => $query->orderBy(
                DB::table('users')
                    ->join('courses', 'courses.id', '=', 'users.course_id')
                    ->select('courses.name')
                    ->whereColumn('users.id', 'registrations.user_id'),
                $direction,
            ),
            'slot' => fn (Builder $query, string $direction) => $query->orderBy(
                Slot::select('name')->whereColumn('slots.id', 'registrations.slot_id'),
                $direction,
            ),
            'group' => fn (Builder $query, string $direction) => $query->orderBy(
                Group::select('name')->whereColumn('groups.id', 'registrations.group_id'),
                $direction,
            ),
            'drinks_alcohol' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'drinks_alcohol', $direction),
            // registrations without queue position come first, then the waiting registrations (-1) and then the queue
            'queue_position' => fn (Builder $query, string $direction) => $query
                ->orderByRaw('CASE WHEN '.$this->wrap('registrations.queue_position')." IS NULL THEN 0 ELSE 1 END {$direction}")
                ->orderBy('registrations.queue_position', $direction),
            'form_responses' => fn (Builder $query, string $direction) => $query->orderByRaw(
                $this->formResponsesAsText()." {$direction}"
            ),
            // ascending: present registrations first
            'is_present' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'is_present', $direction, true),
            // ascending: registrations that fulfil the requirements first
            'fulfils_requirements' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'fulfils_requirements', $direction, true),
        ];
    }

    /**
     * Get the form responses as text for sorting (json columns can not be sorted on every database).
     */
    protected function formResponsesAsText(): string
    {
        $column = $this->wrap('registrations.form_responses');

        return match (Registration::query()->getConnection()->getDriverName()) {
            'pgsql' => "CAST({$column} AS TEXT)",
            'sqlite' => $column,
            default => "CAST({$column} AS CHAR)",
        };
    }
}
