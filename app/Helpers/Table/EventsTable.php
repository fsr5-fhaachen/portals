<?php

namespace App\Helpers\Table;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Events of the tutor dashboard.
 */
class EventsTable extends Table
{
    /**
     * Amount of all participants (see participantsCount()).
     */
    private ?int $participantsCount = null;

    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return Event::query()
            ->with('courses:id,abbreviation')
            ->withCount([
                'registrations',
                // the participants (users without a role) give the share of all participants that registered
                'registrations as participant_registrations_count' => fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->doesntHave('roles')),
            ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param  Event  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'type' => $model->type,
            'consider_alcohol' => $model->consider_alcohol,
            'has_requirements' => $model->has_requirements,
            'registration_from' => $model->registration_from,
            'registration_to' => $model->registration_to,
            'courses' => $model->courses->map(fn ($course) => $course->only(['id', 'abbreviation']))->values()->all(),
            'registrations_count' => $model->registrations_count,
            'participant_registrations_count' => $model->participant_registrations_count,
            'participants_count' => $this->participantsCount(),
        ];
    }

    /**
     * Get the amount of all participants (users without a role), counted once per table.
     */
    private function participantsCount(): int
    {
        return $this->participantsCount ??= User::doesntHave('roles')->count();
    }

    /**
     * {@inheritDoc}
     */
    protected function refreshesAllRows(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderBy('events.sort_order');
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'name' => 'events.name',
            'type' => 'events.type',
            'consider_alcohol' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'consider_alcohol', $direction),
            'courses' => fn (Builder $query, string $direction) => $this->orderByConcatenatedValues(
                $query,
                DB::table('courses')
                    ->join('course_event', 'course_event.course_id', '=', 'courses.id')
                    ->whereColumn('course_event.event_id', 'events.id'),
                'courses.abbreviation',
                $direction,
            ),
            'has_requirements' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'has_requirements', $direction),
            'registration_from' => 'events.registration_from',
            'registrations_count' => 'registrations_count',
        ];
    }
}
