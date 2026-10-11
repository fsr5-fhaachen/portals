<?php

namespace App\Helpers\Table;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Articles of the strichlistensystem (admin).
 */
class ArticlesTable extends Table
{
    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return Article::query()->withCount(['articleActionLogs' => fn ($query) => $query->withTrashed()]);
    }

    /**
     * {@inheritDoc}
     *
     * @param  Article  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'icon' => $model->icon,
            'show_order' => $model->show_order,
            'max_order_amount' => $model->max_order_amount,
            'is_active' => $model->is_active,
            'article_action_logs_count' => $model->article_action_logs_count,
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
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderBy('articles.show_order')->orderBy('articles.name');
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'name' => 'articles.name',
            'icon' => 'articles.icon',
            'show_order' => 'articles.show_order',
            'max_order_amount' => 'articles.max_order_amount',
            // ascending: active articles first
            'is_active' => fn (Builder $query, string $direction) => $this->orderByBoolean($query, 'is_active', $direction, true),
            'article_action_logs_count' => 'article_action_logs_count',
        ];
    }
}
