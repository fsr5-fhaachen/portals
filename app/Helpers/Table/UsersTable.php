<?php

namespace App\Helpers\Table;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Users of the user management.
 */
class UsersTable extends Table
{
    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return User::query()
            ->with([
                'course:id,name,abbreviation,classes,icon',
                'roles:id,name',
            ])
            // the open amount of the strichlistensystem is shown as warning before a user is deleted
            ->withSum('articleActionLogs as article_action_logs_total', 'price')
            ->withSum('strichlistensystemPayments as strichlistensystem_payments_total', 'amount');
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
            'course_id' => $model->course_id,
            'course' => $model->course?->only(['id', 'name', 'abbreviation', 'classes', 'icon']),
            'roles' => $model->roles->map(fn ($role) => $role->only(['id', 'name']))->values()->all(),
            'is_disabled' => (bool) $model->is_disabled,
            'avatar' => $model->avatar,
            'avatarUrl' => $model->avatarUrlUnchecked(),
            'has_pin' => $model->hasPin(),
            'strichlistensystem_open' => (int) $model->article_action_logs_total - (int) $model->strichlistensystem_payments_total,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
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
            'roles' => fn (Builder $query, string $direction) => $this->orderByConcatenatedValues(
                $query,
                DB::table('roles')
                    ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', User::class),
                'roles.name',
                $direction,
            ),
            'avatar' => fn (Builder $query, string $direction) => $query->orderByRaw(
                'CASE WHEN '.$this->wrap('users.avatar')." IS NULL THEN 0 ELSE 1 END {$direction}"
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Bookings and payments change the open amount without a change of the user, so they are checked separately.
     */
    protected function whereChangedSince(Builder $query, Carbon $since): void
    {
        $query->where('users.updated_at', '>=', $since)
            ->orWhereHas('articleActionLogs', fn (Builder $logQuery) => $logQuery->withTrashed()->where('article_action_logs.updated_at', '>=', $since))
            ->orWhereHas('strichlistensystemPayments', fn (Builder $paymentQuery) => $paymentQuery->withTrashed()->where('strichlistensystem_payments.updated_at', '>=', $since));
    }

    /**
     * {@inheritDoc}
     *
     * The open amount can change without a change of the user, so the latest loaded row is always the newest version.
     */
    protected function rowVersion(Model $model): int
    {
        return now()->timestamp;
    }
}
