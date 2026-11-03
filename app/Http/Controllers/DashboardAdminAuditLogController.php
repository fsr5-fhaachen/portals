<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogFormatter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;

class DashboardAdminAuditLogController extends Controller
{
    /**
     * Display the audit log
     */
    public function index(Request $request): Response
    {
        $filters = $this->sanitizeFilters($request);

        $query = Audit::query()
            ->with(['user' => fn (MorphTo $morphTo) => $morphTo->morphWith([User::class => ['roles']])])
            ->orderByDesc('id');

        if ($filters['user_id']) {
            $query->where('user_type', User::class)->where('user_id', $filters['user_id']);
        }
        if ($filters['subject_type']) {
            $query->where('auditable_type', AuditLogFormatter::SUBJECT_TYPES[$filters['subject_type']]['class']);
        }
        if ($filters['subject_id']) {
            $query->where('auditable_id', $filters['subject_id']);
        }
        if ($filters['action']) {
            $query->where('event', $filters['action']);
        }
        if ($filters['from']) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if ($filters['to']) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }
        if ($filters['staff_only']) {
            $query->where(function (Builder $query) {
                $query->whereNull('user_id')->orWhereIn('user_id', self::staffUsers()->select('users.id'));
            });
        }

        $audits = $query->paginate(50)->withQueryString();
        $audits->setCollection((new AuditLogFormatter)->format($audits->getCollection()));

        return Inertia::render('Dashboard/Admin/AuditLog', [
            'audits' => $audits,
            'filters' => $filters,
            'users' => self::staffUsers()
                ->orderBy('firstname')
                ->orderBy('lastname')
                ->get(['id', 'firstname', 'lastname']),
            'subjectTypes' => AuditLogFormatter::subjectTypeOptions(),
            'actions' => AuditLogFormatter::actionOptions(),
        ]);
    }

    /**
     * Get a query for all users with a tutor or admin role.
     *
     * @return Builder<User>
     */
    private static function staffUsers(): Builder
    {
        return User::whereHas('roles', fn (Builder $query) => $query->whereIn('name', AuditLogFormatter::STAFF_ROLES));
    }

    /**
     * Get the valid filters of the request; invalid values are ignored.
     *
     * @return array{user_id: int|null, subject_type: string|null, subject_id: int|null, action: string|null, from: string|null, to: string|null, staff_only: bool}
     */
    private function sanitizeFilters(Request $request): array
    {
        $positiveInteger = function (mixed $value): ?int {
            $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            return $value === false ? null : $value;
        };
        $date = function (mixed $value): ?string {
            if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
                return null;
            }

            return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
        };

        $subjectType = $request->query('subject_type');
        $action = $request->query('action');

        return [
            'user_id' => $positiveInteger($request->query('user_id')),
            'subject_type' => is_string($subjectType) && array_key_exists($subjectType, AuditLogFormatter::SUBJECT_TYPES) ? $subjectType : null,
            'subject_id' => $positiveInteger($request->query('subject_id')),
            'action' => is_string($action) && array_key_exists($action, AuditLogFormatter::ACTIONS) ? $action : null,
            'from' => $date($request->query('from')),
            'to' => $date($request->query('to')),
            'staff_only' => filter_var($request->query('staff_only', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ];
    }
}
