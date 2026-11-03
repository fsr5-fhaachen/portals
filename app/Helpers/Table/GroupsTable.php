<?php

namespace App\Helpers\Table;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Groups of an event.
 */
class GroupsTable extends Table
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
        return Group::query()
            ->where('groups.event_id', $this->eventId)
            ->with('courses:id,abbreviation')
            ->withCount('registrations');
    }

    /**
     * {@inheritDoc}
     *
     * @param  Group  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'event_id' => $model->event_id,
            'courses' => $model->courses->map(fn ($course) => $course->only(['id', 'abbreviation']))->values()->all(),
            'registrations_count' => $model->registrations_count,
        ];
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
    protected function sorts(): array
    {
        return [
            'name' => 'groups.name',
            'courses' => fn (Builder $query, string $direction) => $this->orderByConcatenatedValues(
                $query,
                DB::table('courses')
                    ->join('course_group', 'course_group.course_id', '=', 'courses.id')
                    ->whereColumn('course_group.group_id', 'groups.id'),
                'courses.abbreviation',
                $direction,
            ),
            'registrations_count' => 'registrations_count',
        ];
    }
}
