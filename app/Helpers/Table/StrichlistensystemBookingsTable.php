<?php

namespace App\Helpers\Table;

use App\Models\ArticleActionLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every booking of the strichlistensystem with person, article and time, also the cancelled ones (admin).
 *
 * Request parameters:
 * - user: only the bookings of this user
 */
class StrichlistensystemBookingsTable extends Table
{
    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return ArticleActionLog::withTrashed()->with(['user:id,firstname,lastname', 'article:id,name']);
    }

    /**
     * {@inheritDoc}
     *
     * @param  ArticleActionLog  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'created_at' => $model->created_at,
            'is_cancelled' => $model->trashed(),
            'price' => $model->price,
            'user' => $model->user?->only(['id', 'firstname', 'lastname']),
            'article' => $model->article?->only(['id', 'name']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function searchColumns(): array
    {
        return ['user.firstname', 'user.lastname', 'article.name'];
    }

    /**
     * {@inheritDoc}
     *
     * The newest bookings first.
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderByDesc('article_action_logs.created_at')->orderByDesc('article_action_logs.id');
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'created_at' => 'article_action_logs.created_at',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function applyFilters(Builder $query): void
    {
        if ($this->request->filled('user')) {
            $query->where('article_action_logs.user_id', $this->request->integer('user'));
        }
    }
}
