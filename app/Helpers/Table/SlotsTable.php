<?php

namespace App\Helpers\Table;

use App\Models\Slot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Slots of an event.
 */
class SlotsTable extends Table
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
        return Slot::query()
            ->where('slots.event_id', $this->eventId)
            ->withCount('registrations');
    }

    /**
     * {@inheritDoc}
     *
     * @param  Slot  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'event_id' => $model->event_id,
            'has_requirements' => $model->has_requirements,
            'maximum_participants' => $model->maximum_participants,
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
            'name' => 'slots.name',
            'has_requirements' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'has_requirements', $direction),
            'registrations_count' => 'registrations_count',
        ];
    }
}
