<?php

namespace App\Helpers\Table;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Persons of the strichlistensystem kiosk. Filters: course (course id) and role (tutor|special).
 */
class KioskPersonsTable extends Table
{
    /**
     * Roles that can be used as filter.
     *
     * @var array<int, string>
     */
    public const ROLE_FILTERS = ['tutor', 'special'];

    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return User::query()
            ->where('users.is_disabled', false)
            ->with(['course', 'roles:id,name']);
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
            'course' => $model->course,
            'avatar_url' => $model->avatar ? route('strichlistensystem.kiosk.avatar', ['user' => $model->id], false) : null,
            'roles' => $model->roles->pluck('name')->values()->all(),
            'has_pin' => $model->hasPin(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function perPage(): int
    {
        return 48;
    }

    /**
     * {@inheritDoc}
     */
    protected function searchColumns(): array
    {
        return ['firstname', 'lastname'];
    }

    /**
     * {@inheritDoc}
     */
    protected function applyFilters(Builder $query): void
    {
        $courseId = $this->request->query('course');
        if (is_string($courseId) && ctype_digit($courseId)) {
            $query->where('users.course_id', (int) $courseId);
        }

        $role = $this->request->query('role');
        if (in_array($role, self::ROLE_FILTERS, true)) {
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', $role));
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderBy('users.firstname')->orderBy('users.lastname');
    }
}
