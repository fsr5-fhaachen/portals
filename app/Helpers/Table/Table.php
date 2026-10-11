<?php

namespace App\Helpers\Table;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\ScrollProp;

/**
 * Base class for tables that are loaded page by page (infinite scroll) with server side search and sorting.
 *
 * Request parameters:
 * - search: search terms (every term must match at least one search column)
 * - sort / direction: sort key (see sorts()) and direction (asc|desc)
 * - page: page of the infinite scroll
 * - changes_since / loaded_ids: live update, returns the changed and removed rows of the loaded ids
 */
abstract class Table
{
    /**
     * Rows per page.
     *
     * @var int
     */
    public const PER_PAGE = 50;

    /**
     * Maximum amount of loaded ids that are checked for changes.
     *
     * @var int
     */
    protected const MAX_LOADED_IDS = 2000;

    public function __construct(protected Request $request) {}

    /**
     * Base query with the scope of the table (without search and sorting).
     */
    abstract protected function query(): Builder;

    /**
     * Transform a model into the row that is sent to the frontend.
     *
     * @return array<string, mixed>
     */
    abstract protected function row(Model $model): array;

    /**
     * Searchable columns. Columns of relations are written as "relation.column".
     *
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return [];
    }

    /**
     * Sort keys of the table. A key maps to a column or to a closure that applies the sorting.
     *
     * @return array<string, string|Closure(Builder, string): void>
     */
    protected function sorts(): array
    {
        return [];
    }

    /**
     * Apply the default sorting if no sort key is selected.
     */
    protected function applyDefaultSort(Builder $query): void {}

    /**
     * Apply additional filters of the table (e.g. from request parameters).
     */
    protected function applyFilters(Builder $query): void {}

    /**
     * Rows per page.
     */
    protected function perPage(): int
    {
        return self::PER_PAGE;
    }

    /**
     * Whether every loaded row is returned on a live update. Used for small tables with aggregated
     * values (e.g. amount of registrations) that change without changing the row itself.
     */
    protected function refreshesAllRows(): bool
    {
        return false;
    }

    /**
     * Restrict the query to rows that changed since the given time.
     */
    protected function whereChangedSince(Builder $query, Carbon $since): void
    {
        $query->where($query->getModel()->qualifyColumn('updated_at'), '>=', $since);
    }

    /**
     * Get the version of a row. The frontend keeps the newer version of a row if it got the row multiple times.
     */
    protected function rowVersion(Model $model): int
    {
        if ($this->refreshesAllRows()) {
            return now()->timestamp;
        }

        return (int) $model->updated_at?->timestamp;
    }

    /**
     * Get the rows of the current page as infinite scroll prop.
     */
    public function scroll(): ScrollProp
    {
        return Inertia::scroll(fn () => $this->sortedQuery()
            ->simplePaginate($this->perPage())
            ->through(fn (Model $model) => $this->toRow($model)));
    }

    /**
     * Check if the request asks for the live update of the table.
     */
    public function wantsChanges(): bool
    {
        return $this->request->has('changes_since');
    }

    /**
     * Get the live update of the loaded rows as json response.
     */
    public function changesResponse(): JsonResponse
    {
        return response()->json($this->changes());
    }

    /**
     * Get the live update of the loaded rows: changed rows, removed rows and ids of new rows.
     *
     * @return array{time: int, rows: array<int, array<string, mixed>>, removed_ids: array<int, int>, new_ids: array<int, int>}
     */
    public function changes(): array
    {
        // the time is taken before the queries, so that no change is missed
        $time = now()->timestamp;
        $since = Carbon::createFromTimestamp((int) $this->request->query('changes_since'), config('app.timezone'));

        $loadedIds = collect(explode(',', (string) $this->request->query('loaded_ids', '')))
            ->filter(fn (string $id) => ctype_digit($id))
            ->map(fn (string $id) => (int) $id)
            ->unique()
            ->take(self::MAX_LOADED_IDS)
            ->values()
            ->all();

        $model = $this->query()->getModel();
        $qualifiedKeyName = $model->getQualifiedKeyName();

        $existingIds = $loadedIds
            ? $this->filteredQuery()->whereIn($qualifiedKeyName, $loadedIds)->pluck($qualifiedKeyName)->map(fn ($id) => (int) $id)->all()
            : [];

        $rows = [];
        if ($existingIds) {
            $changedQuery = $this->filteredQuery()->whereIn($qualifiedKeyName, $existingIds);
            if (! $this->refreshesAllRows()) {
                $changedQuery->where(fn (Builder $query) => $this->whereChangedSince($query, $since));
            }

            $rows = $changedQuery->get()->map(fn (Model $model) => $this->toRow($model))->values()->all();
        }

        $newIds = $this->filteredQuery()
            ->whereNotIn($qualifiedKeyName, $loadedIds)
            ->where($model->qualifyColumn('created_at'), '>=', $since)
            ->limit(100)
            ->pluck($qualifiedKeyName)
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'time' => $time,
            'rows' => $rows,
            'removed_ids' => array_values(array_diff($loadedIds, $existingIds)),
            'new_ids' => $newIds,
        ];
    }

    /**
     * Get the current sort key and direction of the request.
     *
     * @return array{0: string|null, 1: string}
     */
    public function currentSort(): array
    {
        $sortKey = $this->request->query('sort');
        $direction = $this->request->query('direction') === 'desc' ? 'desc' : 'asc';

        if (! is_string($sortKey) || ! array_key_exists($sortKey, $this->sorts())) {
            return [null, $direction];
        }

        return [$sortKey, $direction];
    }

    /**
     * Query with scope, filters and search.
     */
    protected function filteredQuery(): Builder
    {
        $query = $this->query();
        $this->applyFilters($query);
        $this->applySearch($query);

        return $query;
    }

    /**
     * Query with scope, filters, search and sorting. The primary key is always added to get a stable order across pages.
     */
    protected function sortedQuery(): Builder
    {
        $query = $this->filteredQuery();

        [$sortKey, $direction] = $this->currentSort();
        if ($sortKey !== null) {
            $sort = $this->sorts()[$sortKey];

            if ($sort instanceof Closure) {
                $sort($query, $direction);
            } else {
                $query->orderBy($sort, $direction);
            }
        } else {
            $this->applyDefaultSort($query);
        }

        return $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    /**
     * Apply the search. The search is split into terms and every term must match at least one search column.
     */
    protected function applySearch(Builder $query): void
    {
        $terms = preg_split('/\s+/', trim((string) $this->request->query('search', '')), -1, PREG_SPLIT_NO_EMPTY);

        if (! $terms || ! $this->searchColumns()) {
            return;
        }

        foreach (array_slice($terms, 0, 5) as $term) {
            $query->where(function (Builder $termQuery) use ($term) {
                foreach ($this->searchColumns() as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $relationColumn] = explode('.', $column, 2);
                        $termQuery->orWhereHas($relation, fn (Builder $relationQuery) => $relationQuery->whereLike($relationQuery->getModel()->qualifyColumn($relationColumn), '%'.$term.'%'));
                    } else {
                        $termQuery->orWhereLike($termQuery->getModel()->qualifyColumn($column), '%'.$term.'%');
                    }
                }
            });
        }
    }

    /**
     * Transform a model into a row with version.
     *
     * @return array<string, mixed>
     */
    protected function toRow(Model $model): array
    {
        return $this->row($model) + ['_version' => $this->rowVersion($model)];
    }

    /**
     * Order by a boolean column (false before true in ascending order). Null is treated as false.
     */
    protected function orderByBoolean(Builder $query, string $column, string $direction, bool $trueFirst = false): void
    {
        $column = $this->wrap($query->getModel()->qualifyColumn($column));
        $trueValue = $trueFirst ? 0 : 1;
        $falseValue = $trueFirst ? 1 : 0;

        $query->orderByRaw("CASE WHEN {$column} THEN {$trueValue} ELSE {$falseValue} END {$direction}");
    }

    /**
     * Order by the concatenated (alphabetically sorted) values of a related column. The values query must contain
     * the joins and the condition to the outer table (whereColumn). Rows without values come first in ascending order.
     */
    protected function orderByConcatenatedValues(Builder $query, QueryBuilder $valuesQuery, string $valueColumn, string $direction): void
    {
        $driver = $query->getModel()->getConnection()->getDriverName();
        $column = $this->wrap($valueColumn);

        $aggregate = match ($driver) {
            'pgsql' => "STRING_AGG({$column}, '' ORDER BY {$column})",
            // sqlite does not support an order in group_concat, it is only used for tests
            'sqlite' => "GROUP_CONCAT({$column}, '')",
            default => "GROUP_CONCAT({$column} ORDER BY {$column} SEPARATOR '')",
        };

        $aggregateQuery = (clone $valuesQuery)->selectRaw("COALESCE({$aggregate}, '')");

        $query->orderByRaw("({$aggregateQuery->toSql()}) {$direction}", $aggregateQuery->getBindings());
    }

    /**
     * Wrap a column for raw sql.
     */
    protected function wrap(string $column): string
    {
        return $this->query()->getQuery()->getGrammar()->wrap($column);
    }
}
