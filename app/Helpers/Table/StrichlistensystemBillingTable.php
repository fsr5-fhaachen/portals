<?php

namespace App\Helpers\Table;

use App\Models\ArticleActionLog;
use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Balances (bookings, payments and open amount) of all users of the strichlistensystem (admin).
 */
class StrichlistensystemBillingTable extends Table
{
    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return User::query()
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
            'total' => $total,
            'paid' => $paid,
            'open' => $total - $paid,
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
            'article_action_logs_count' => 'article_action_logs_count',
            'total' => fn (Builder $query, string $direction) => $query->orderBy($this->totalQuery(), $direction),
            'paid' => fn (Builder $query, string $direction) => $query->orderBy($this->paidQuery(), $direction),
            'open' => function (Builder $query, string $direction) {
                $totalQuery = $this->totalQuery()->toBase();
                $paidQuery = $this->paidQuery()->toBase();

                $query->orderByRaw(
                    "(({$totalQuery->toSql()}) - ({$paidQuery->toSql()})) {$direction}",
                    [...$totalQuery->getBindings(), ...$paidQuery->getBindings()],
                );
            },
        ];
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
