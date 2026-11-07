<?php

namespace App\Helpers\Table;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Participants (users without a role) that are not registered for an event (negative list).
 */
class NotRegisteredUsersTable extends Table
{
    public function __construct(Request $request, protected int $eventId)
    {
        parent::__construct($request);
    }

    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return User::query()
            ->doesntHave('roles')
            ->whereDoesntHave('registrations', fn (Builder $query) => $query->where('registrations.event_id', $this->eventId))
            ->with('course:id,name,abbreviation,classes');
    }

    /**
     * {@inheritDoc}
     *
     * @param  User  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'firstname' => $model->firstname,
            'lastname' => $model->lastname,
            'email' => $model->email,
            'course' => $model->course?->only(['id', 'name', 'abbreviation', 'classes']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function searchColumns(): array
    {
        return ['firstname', 'lastname', 'email'];
    }

    /**
     * {@inheritDoc}
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderBy('users.lastname')->orderBy('users.firstname');
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'firstname' => 'users.firstname',
            'lastname' => 'users.lastname',
            'email' => 'users.email',
            'course' => fn (Builder $query, string $direction) => $query->orderBy(
                Course::select('abbreviation')->whereColumn('courses.id', 'users.course_id'),
                $direction,
            ),
        ];
    }
}
