<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Events\AuditCustom;

class AuditRecorder
{
    /**
     * Record a custom audit event (e.g. role changes or logins) for the given model.
     *
     * The audit flags live on the model instance and are always reset afterwards,
     * so no state leaks into later saves of the same instance or other requests.
     *
     * @param  Model&Auditable  $model
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function record(Auditable $model, string $event, array $oldValues = [], array $newValues = []): void
    {
        $model->auditEvent = $event;
        $model->isCustomEvent = true;
        $model->auditCustomOld = $oldValues;
        $model->auditCustomNew = $newValues;

        try {
            Event::dispatch(new AuditCustom($model));
        } finally {
            $model->auditEvent = null;
            $model->isCustomEvent = false;
            $model->auditCustomOld = null;
            $model->auditCustomNew = null;
        }
    }
}
