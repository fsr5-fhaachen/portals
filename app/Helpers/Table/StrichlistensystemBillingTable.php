<?php

namespace App\Helpers\Table;

use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Balances (bookings, payments and open amount) of all users of the strichlistensystem (admin).
 */
class StrichlistensystemBillingTable extends Table
{
    /**
     * Articles by id in the order of the articles (see articles()).
     *
     * @var Collection<int, Article>|null
     */
    private ?Collection $articles = null;

    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return User::query()
            // amount per article of the bookings (without cancelled ones), loaded grouped for all users of the page
            ->with(['articleActionLogs' => fn ($query) => $query
                ->selectRaw('user_id, article_id, count(*) as amount')
                ->groupBy('user_id', 'article_id')])
            ->withSum('articleActionLogs as article_action_logs_total', 'price')
            ->withCount('articleActionLogs')
            ->withSum('strichlistensystemPayments as strichlistensystem_payments_total', 'amount');
    }

    /**
     * {@inheritDoc}
     *
     * @param  User  $model
     */
    protected function row(Model $model): array
    {
        $total = (int) $model->article_action_logs_total;
        $paid = (int) $model->strichlistensystem_payments_total;

        return [
            'id' => $model->id,
            'firstname' => $model->firstname,
            'lastname' => $model->lastname,
            'email' => $model->email,
            'is_disabled' => (bool) $model->is_disabled,
            'article_action_logs_count' => $model->article_action_logs_count,
            'articles' => $this->articleAmounts($model),
            'total' => $total,
            'paid' => $paid,
            'open' => $total - $paid,
        ];
    }

    /**
     * Get the booked amount per article of the user in the order of the articles.
     *
     * @return list<array{name: string, amount: int}>
     */
    private function articleAmounts(User $user): array
    {
        $articles = $this->articles();

        return $user->articleActionLogs
            ->filter(fn (ArticleActionLog $amount) => $articles->has($amount->article_id))
            ->sortBy(fn (ArticleActionLog $amount) => $articles->keys()->search($amount->article_id))
            ->map(fn (ArticleActionLog $amount) => [
                'name' => $articles[$amount->article_id]->name,
                'amount' => (int) $amount->amount,
            ])
            ->values()
            ->all();
    }

    /**
     * Get the articles by id in the order of the articles, loaded once per table.
     *
     * @return Collection<int, Article>
     */
    private function articles(): Collection
    {
        return $this->articles ??= Article::orderBy('show_order')->orderBy('name')->get(['id', 'name'])->keyBy('id');
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
            'article_action_logs_count' => 'article_action_logs_count',
            'total' => fn (Builder $query, string $direction) => $query->orderBy($this->totalQuery(), $direction),
            'paid' => fn (Builder $query, string $direction) => $query->orderBy($this->paidQuery(), $direction),
            'open' => function (Builder $query, string $direction) {
                [$openSql, $openBindings] = $this->openAmountSql();

                $query->orderByRaw("{$openSql} {$direction}", $openBindings);
            },
        ];
    }

    /**
     * {@inheritDoc}
     *
     * With "open" only persons with an open amount are shown. The search always finds every person,
     * also persons without an open amount.
     */
    protected function applyFilters(Builder $query): void
    {
        if (! $this->request->boolean('open') || trim((string) $this->request->query('search', '')) !== '') {
            return;
        }

        [$openSql, $openBindings] = $this->openAmountSql();

        $query->whereRaw("{$openSql} > 0", $openBindings);
    }

    /**
     * {@inheritDoc}
     *
     * Bookings, cancellations, price changes and payments do not change the user, so they are checked separately.
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
     * The balance can change without a change of the user, so the latest loaded row is always the newest version.
     */
    protected function rowVersion(Model $model): int
    {
        return now()->timestamp;
    }

    /**
     * Open amount of the user (bookings - payments) as sql expression with its bindings.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function openAmountSql(): array
    {
        $totalQuery = $this->totalQuery()->toBase();
        $paidQuery = $this->paidQuery()->toBase();

        return [
            "(({$totalQuery->toSql()}) - ({$paidQuery->toSql()}))",
            [...$totalQuery->getBindings(), ...$paidQuery->getBindings()],
        ];
    }

    /**
     * Sum of the prices of the bookings (without cancelled ones) of the user.
     */
    private function totalQuery(): Builder
    {
        return ArticleActionLog::query()
            ->selectRaw('COALESCE(SUM('.$this->wrap('article_action_logs.price').'), 0)')
            ->whereColumn('article_action_logs.user_id', 'users.id');
    }

    /**
     * Sum of the payments (without undone ones) of the user.
     */
    private function paidQuery(): Builder
    {
        return StrichlistensystemPayment::query()
            ->selectRaw('COALESCE(SUM('.$this->wrap('strichlistensystem_payments.amount').'), 0)')
            ->whereColumn('strichlistensystem_payments.user_id', 'users.id');
    }
}
