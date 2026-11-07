<?php

namespace App\Helpers\Table;

use App\Models\Workshop;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Workshop sessions of a workshop booking event with the amount of assigned participants (like the slots of an event).
 */
class WorkshopSessionsTable extends Table
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
        return WorkshopSession::query()
            ->whereHas('workshop', fn (Builder $query) => $query->where('workshops.event_id', $this->eventId))
            ->with(['workshop:id,name', 'block:id,name,starts_at,ends_at'])
            ->withCount('assignments');
    }

    /**
     * {@inheritDoc}
     *
     * @param  WorkshopSession  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'workshop_name' => $model->workshop?->name,
            'block_name' => $model->block?->name,
            'starts_at' => $model->block?->starts_at,
            'ends_at' => $model->block?->ends_at,
            'location' => $model->location,
            'is_cancelled' => (bool) $model->is_cancelled,
            'maximum_participants' => $model->maximum_participants,
            'assignments_count' => $model->assignments_count,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * The amount of participants changes without a change of the session.
     */
    protected function refreshesAllRows(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function searchColumns(): array
    {
        return ['workshop.name', 'location'];
    }

    /**
     * {@inheritDoc}
     *
     * Ordered by time, then by workshop.
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderBy($this->blockStartQuery())->orderBy($this->workshopNameQuery());
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'starts_at' => fn (Builder $query, string $direction) => $query->orderBy($this->blockStartQuery(), $direction)->orderBy($this->workshopNameQuery()),
            'workshop_name' => fn (Builder $query, string $direction) => $query->orderBy($this->workshopNameQuery(), $direction)->orderBy($this->blockStartQuery()),
            'location' => 'workshop_sessions.location',
            'assignments_count' => 'assignments_count',
        ];
    }

    /**
     * Subquery of the start of the block of the session.
     */
    private function blockStartQuery(): Builder
    {
        return WorkshopBlock::select('starts_at')->whereColumn('workshop_blocks.id', 'workshop_sessions.workshop_block_id');
    }

    /**
     * Subquery of the name of the workshop of the session.
     */
    private function workshopNameQuery(): Builder
    {
        return Workshop::select('name')->whereColumn('workshops.id', 'workshop_sessions.workshop_id');
    }
}
