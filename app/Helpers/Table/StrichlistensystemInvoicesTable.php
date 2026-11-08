<?php

namespace App\Helpers\Table;

use App\Models\StrichlistensystemInvoice;
use App\Models\StrichlistensystemPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoices of the strichlistensystem with their status (admin).
 *
 * The payments of a person cover the invoices in their order, so an invoice is paid if the payments of the person are
 * at least the sum of the invoice and all earlier (not cancelled) invoices of the person.
 */
class StrichlistensystemInvoicesTable extends Table
{
    /**
     * {@inheritDoc}
     */
    protected function query(): Builder
    {
        return StrichlistensystemInvoice::query()
            ->with(['user:id,firstname,lastname,email', 'createdBy:id,firstname,lastname'])
            ->select('strichlistensystem_invoices.*')
            ->addSelect([
                'payments_total' => StrichlistensystemPayment::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('strichlistensystem_payments.user_id', 'strichlistensystem_invoices.user_id'),
                'invoiced_until' => StrichlistensystemInvoice::from('strichlistensystem_invoices as earlier_invoices')
                    ->selectRaw('COALESCE(SUM(earlier_invoices.subtotal), 0)')
                    ->whereColumn('earlier_invoices.user_id', 'strichlistensystem_invoices.user_id')
                    ->whereColumn('earlier_invoices.sequence', '<=', 'strichlistensystem_invoices.sequence')
                    ->whereNull('earlier_invoices.cancelled_at'),
            ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param  StrichlistensystemInvoice  $model
     */
    protected function row(Model $model): array
    {
        return [
            'id' => $model->id,
            'number' => $model->number,
            'title' => $model->title,
            'issued_on' => $model->issued_on?->toDateString(),
            'due_on' => $model->due_on?->toDateString(),
            'recipient' => $model->recipient,
            'user' => $model->user?->only(['id', 'firstname', 'lastname', 'email']),
            'created_by' => $model->createdBy?->only(['id', 'firstname', 'lastname']),
            'subtotal' => $model->subtotal,
            'paid' => $model->paid,
            'amount_due' => $model->amount_due,
            'status' => $this->status($model),
        ];
    }

    /**
     * Get the status of the invoice: cancelled, paid or open.
     */
    private function status(StrichlistensystemInvoice $invoice): string
    {
        if ($invoice->isCancelled()) {
            return 'cancelled';
        }

        return (int) $invoice->payments_total >= (int) $invoice->invoiced_until ? 'paid' : 'open';
    }

    /**
     * {@inheritDoc}
     *
     * Payments change the status without a change of the invoice.
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
        return ['number', 'title', 'user.firstname', 'user.lastname', 'user.email'];
    }

    /**
     * {@inheritDoc}
     *
     * The newest invoices first.
     */
    protected function applyDefaultSort(Builder $query): void
    {
        $query->orderByDesc('strichlistensystem_invoices.sequence');
    }

    /**
     * {@inheritDoc}
     */
    protected function sorts(): array
    {
        return [
            'number' => 'strichlistensystem_invoices.sequence',
            'issued_on' => 'strichlistensystem_invoices.issued_on',
            'amount_due' => 'strichlistensystem_invoices.amount_due',
        ];
    }
}
