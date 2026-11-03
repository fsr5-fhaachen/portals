<?php

namespace App\Helpers;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Course;
use App\Models\CourseEvent;
use App\Models\CourseGroup;
use App\Models\Duel;
use App\Models\Event;
use App\Models\ExtraPoint;
use App\Models\Group;
use App\Models\GroupTutor;
use App\Models\Page;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\State;
use App\Models\Station;
use App\Models\StationTutor;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;

/**
 * Turns raw audits into human readable log entries.
 *
 * Use one instance per request: resolved names are cached on the instance.
 */
class AuditLogFormatter
{
    /**
     * Audited models with their filter key and German label.
     *
     * @var array<string, array{class: class-string, label: string}>
     */
    public const SUBJECT_TYPES = [
        'Registration' => ['class' => Registration::class, 'label' => 'Anmeldung'],
        'User' => ['class' => User::class, 'label' => 'Nutzer'],
        'Group' => ['class' => Group::class, 'label' => 'Gruppe'],
        'Event' => ['class' => Event::class, 'label' => 'Veranstaltung'],
        'Slot' => ['class' => Slot::class, 'label' => 'Slot'],
        'Course' => ['class' => Course::class, 'label' => 'Studiengang'],
        'Page' => ['class' => Page::class, 'label' => 'Seite'],
        'State' => ['class' => State::class, 'label' => 'Modul-Zustand'],
        'GroupTutor' => ['class' => GroupTutor::class, 'label' => 'Gruppen-Tutor'],
        'Station' => ['class' => Station::class, 'label' => 'Station'],
        'StationTutor' => ['class' => StationTutor::class, 'label' => 'Stations-Tutor'],
        'Stop' => ['class' => Stop::class, 'label' => 'Stopp'],
        'CourseEvent' => ['class' => CourseEvent::class, 'label' => 'Studiengang der Veranstaltung'],
        'CourseGroup' => ['class' => CourseGroup::class, 'label' => 'Studiengang der Gruppe'],
        'Competition' => ['class' => Competition::class, 'label' => 'Wettbewerb'],
        'CompetitionTeam' => ['class' => CompetitionTeam::class, 'label' => 'Team'],
        'Duel' => ['class' => Duel::class, 'label' => 'Duell'],
        'ExtraPoint' => ['class' => ExtraPoint::class, 'label' => 'Sonderpunkte'],
    ];

    /**
     * Audit events with their German label.
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        'created' => 'Angelegt',
        'updated' => 'Geändert',
        'deleted' => 'Gelöscht',
        'restored' => 'Wiederhergestellt',
        'rolesUpdated' => 'Rollen geändert',
        'tutorLogin' => 'Tutor-Login',
        'adminLogin' => 'Admin-Login',
        'tutorLoginFailed' => 'Tutor-Login fehlgeschlagen',
        'adminLoginFailed' => 'Admin-Login fehlgeschlagen',
    ];

    /**
     * Roles whose actions count as staff actions.
     *
     * @var list<string>
     */
    public const STAFF_ROLES = ['tutor', 'stage tutor', 'esa', 'admin', 'super admin'];

    /**
     * Fields that are never shown, not even for old audits.
     *
     * @var list<string>
     */
    private const HIDDEN_FIELDS = ['remember_token', 'pin', 'password'];

    /**
     * Fields that hold booleans (stored as 0/1 for models without casts).
     *
     * @var list<string>
     */
    private const BOOLEAN_FIELDS = [
        'is_disabled',
        'is_present',
        'fulfils_requirements',
        'drinks_alcohol',
        'has_requirements',
        'consider_alcohol',
        'show_on_registration',
        'is_open',
        'bonus_per_team',
        'is_tutor',
        'is_admin',
    ];

    /**
     * Foreign key fields and the model they point to.
     *
     * @var array<string, class-string>
     */
    private const FOREIGN_KEYS = [
        'user_id' => User::class,
        'created_by' => User::class,
        'updated_by' => User::class,
        'group_id' => Group::class,
        'slot_id' => Slot::class,
        'event_id' => Event::class,
        'course_id' => Course::class,
        'station_id' => Station::class,
        'competition_id' => Competition::class,
        'team_a_id' => CompetitionTeam::class,
        'team_b_id' => CompetitionTeam::class,
        'competition_team_id' => CompetitionTeam::class,
    ];

    /**
     * German labels of the audited fields.
     *
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'firstname' => 'Vorname',
        'lastname' => 'Nachname',
        'email' => 'E-Mail',
        'avatar' => 'Avatar',
        'roles' => 'Rollen',
        'name' => 'Name',
        'title' => 'Titel',
        'slug' => 'Slug',
        'content' => 'Inhalt',
        'description' => 'Beschreibung',
        'abbreviation' => 'Kürzel',
        'icon' => 'Icon',
        'classes' => 'Farbklassen',
        'type' => 'Typ',
        'key' => 'Schlüssel',
        'value' => 'Wert',
        'form' => 'Formular',
        'form_responses' => 'Formularantworten',
        'sort_order' => 'Sortierung',
        'registration_from' => 'Anmeldung ab',
        'registration_to' => 'Anmeldung bis',
        'maximum_participants' => 'Max. Teilnehmende',
        'queue_position' => 'Wartelistenplatz',
        'telegram_group_link' => 'Telegram-Link',
        'arrival_at' => 'Ankunft',
        'departure_at' => 'Abfahrt',
        'is_disabled' => 'Deaktiviert',
        'is_present' => 'Anwesend',
        'fulfils_requirements' => 'Voraussetzungen erfüllt',
        'drinks_alcohol' => 'Trinkt Alkohol',
        'has_requirements' => 'Hat Voraussetzungen',
        'consider_alcohol' => 'Alkohol berücksichtigen',
        'show_on_registration' => 'Bei Registrierung anzeigen',
        'is_tutor' => 'Tutor',
        'is_admin' => 'Admin',
        'user_id' => 'Nutzer',
        'created_by' => 'Eingetragen von',
        'updated_by' => 'Geändert von',
        'group_id' => 'Gruppe',
        'slot_id' => 'Slot',
        'event_id' => 'Veranstaltung',
        'course_id' => 'Studiengang',
        'station_id' => 'Station',
        'competition_id' => 'Wettbewerb',
        'team_a_id' => 'Team A',
        'team_b_id' => 'Team B',
        'competition_team_id' => 'Team',
        'points_win' => 'Punkte Sieg',
        'points_draw' => 'Punkte Unentschieden',
        'points_loss' => 'Punkte Niederlage',
        'bonus_pool' => 'Max. Bonus',
        'bonus_per_team' => 'Bonus pro Team',
        'is_open' => 'Offen',
        'result' => 'Ergebnis',
        'bonus_a' => 'Bonus Team A',
        'bonus_b' => 'Bonus Team B',
        'note' => 'Notiz',
        'points' => 'Punkte',
        'reason' => 'Grund',
    ];

    /**
     * German labels of the duel results.
     *
     * @var array<string, string>
     */
    private const RESULT_LABELS = [
        Duel::RESULT_TEAM_A => 'Sieg Team A',
        Duel::RESULT_DRAW => 'Unentschieden',
        Duel::RESULT_TEAM_B => 'Sieg Team B',
    ];

    private const MAX_VALUE_LENGTH = 200;

    /**
     * Attributes of the audited models, keyed by class and id.
     *
     * @var array<class-string, array<int, array{attributes: array<string, mixed>, deleted: bool}>>
     */
    private array $subjects = [];

    /**
     * Display names of referenced models, keyed by class and id.
     *
     * @var array<class-string, array<int, string>>
     */
    private array $names = [];

    /**
     * Format the given audits into log entries.
     *
     * @param  Collection<int, Audit>  $audits
     * @return Collection<int, array{
     *     id: int,
     *     created_at: string|null,
     *     actor: array{id: int, name: string, roles: list<string>}|null,
     *     action: string,
     *     action_label: string,
     *     subject_type: string,
     *     subject_type_label: string,
     *     subject_id: int,
     *     subject_label: string,
     *     subject_deleted: bool,
     *     changes: list<array{field: string, label: string, old: string|null, new: string|null}>
     * }>
     */
    public function format(Collection $audits): Collection
    {
        $this->loadSubjects($audits);
        $this->loadNames($audits);

        return $audits->map(fn (Audit $audit) => $this->formatAudit($audit))->values();
    }

    /**
     * Get the subject type filter options.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function subjectTypeOptions(): array
    {
        return collect(self::SUBJECT_TYPES)
            ->map(fn (array $type, string $key) => ['value' => $key, 'label' => $type['label']])
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * Get the action filter options.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function actionOptions(): array
    {
        return collect(self::ACTIONS)
            ->map(fn (string $label, string $key) => ['value' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * Load the audited models, falling back to their last known state for deleted ones.
     *
     * @param  Collection<int, Audit>  $audits
     */
    private function loadSubjects(Collection $audits): void
    {
        $idsByClass = $audits
            ->filter(fn (Audit $audit) => $this->isKnownClass($audit->auditable_type))
            ->groupBy('auditable_type')
            ->map(fn (Collection $group) => $group->pluck('auditable_id')->map(fn ($id) => (int) $id)->unique()->values());

        foreach ($idsByClass as $class => $ids) {
            foreach ($class::whereIn('id', $ids)->get() as $model) {
                $this->subjects[$class][$model->id] = ['attributes' => $model->getAttributes(), 'deleted' => false];
            }

            $missingIds = $ids->reject(fn (int $id) => isset($this->subjects[$class][$id]));
            foreach ($this->lastKnownAttributes($class, $missingIds) as $id => $attributes) {
                $this->subjects[$class][$id] = ['attributes' => $attributes, 'deleted' => true];
            }
        }

        // subjects without any trace left: use the values of the audit itself
        foreach ($audits as $audit) {
            $class = $audit->auditable_type;
            $id = (int) $audit->auditable_id;
            if (! isset($this->subjects[$class][$id])) {
                $this->subjects[$class][$id] = [
                    'attributes' => array_merge($audit->old_values ?? [], $audit->new_values ?? []),
                    'deleted' => true,
                ];
            }
        }
    }

    /**
     * Resolve the names of all referenced models with one query per model class.
     *
     * @param  Collection<int, Audit>  $audits
     */
    private function loadNames(Collection $audits): void
    {
        $idsByClass = [];

        $collect = function (array $values) use (&$idsByClass): void {
            foreach (self::FOREIGN_KEYS as $field => $class) {
                if (isset($values[$field]) && is_numeric($values[$field])) {
                    $idsByClass[$class][] = (int) $values[$field];
                }
            }
        };

        foreach ($audits as $audit) {
            $collect($audit->old_values ?? []);
            $collect($audit->new_values ?? []);

            if ($audit->user_id && ! $audit->user) {
                $idsByClass[User::class][] = (int) $audit->user_id;
            }
        }
        foreach ($this->subjects as $subjectsOfClass) {
            foreach ($subjectsOfClass as $subject) {
                $collect($subject['attributes']);
            }
        }

        foreach ($idsByClass as $class => $ids) {
            $ids = collect($ids)->unique()->values();

            // audited models are already loaded
            foreach ($this->subjects[$class] ?? [] as $id => $subject) {
                $this->names[$class][$id] = $this->nameOf($class, $subject['attributes'], $subject['deleted']);
            }
            $ids = $ids->reject(fn (int $id) => isset($this->names[$class][$id]));
            if ($ids->isEmpty()) {
                continue;
            }

            $columns = $class === User::class ? ['id', 'firstname', 'lastname'] : ['id', 'name'];
            foreach ($class::whereIn('id', $ids)->get($columns) as $model) {
                $this->names[$class][$model->id] = $this->nameOf($class, $model->getAttributes(), false);
            }

            $missingIds = $ids->reject(fn (int $id) => isset($this->names[$class][$id]));
            foreach ($this->lastKnownAttributes($class, $missingIds) as $id => $attributes) {
                $this->names[$class][$id] = $this->nameOf($class, $attributes, true);
            }
        }
    }

    /**
     * Get the attributes of deleted models from their "deleted" audits.
     *
     * @param  class-string  $class
     * @param  Collection<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function lastKnownAttributes(string $class, Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        return Audit::query()
            ->where('event', 'deleted')
            ->where('auditable_type', $class)
            ->whereIn('auditable_id', $ids)
            ->orderBy('id')
            ->get(['auditable_id', 'old_values'])
            ->mapWithKeys(fn (Audit $audit) => [(int) $audit->auditable_id => $audit->old_values ?? []])
            ->all();
    }

    /**
     * Get the display name of a referenced model.
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $attributes
     */
    private function nameOf(string $class, array $attributes, bool $deleted): string
    {
        $name = $class === User::class
            ? trim(($attributes['firstname'] ?? '').' '.($attributes['lastname'] ?? ''))
            : (string) ($attributes['name'] ?? '');

        if ($name === '') {
            $name = '#'.($attributes['id'] ?? '?');
        }

        return $deleted ? $name.' (gelöscht)' : $name;
    }

    /**
     * Get the name of the model the foreign key field points to.
     */
    private function resolveForeignKey(string $field, mixed $id): string
    {
        $class = self::FOREIGN_KEYS[$field];

        return $this->names[$class][(int) $id] ?? '#'.$id;
    }

    /**
     * Describe the audited model, e.g. "Max Muster · Kneipentour" for a registration.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function describeSubject(string $class, array $attributes): string
    {
        $reference = fn (string $field): string => isset($attributes[$field])
            ? $this->resolveForeignKey($field, $attributes[$field])
            : '?';

        $description = match ($class) {
            User::class => trim(($attributes['firstname'] ?? '').' '.($attributes['lastname'] ?? '')),
            Registration::class => $reference('user_id').' · '.$reference('event_id'),
            GroupTutor::class => $reference('user_id').' · '.$reference('group_id'),
            StationTutor::class => $reference('user_id').' · '.$reference('station_id'),
            Stop::class => $reference('group_id').' · '.$reference('station_id'),
            CourseEvent::class => $reference('course_id').' · '.$reference('event_id'),
            CourseGroup::class => $reference('course_id').' · '.$reference('group_id'),
            Duel::class => $reference('team_a_id').' vs. '.$reference('team_b_id'),
            ExtraPoint::class => $reference('competition_team_id').' ('.sprintf('%+d', (int) ($attributes['points'] ?? 0)).')',
            Page::class => (string) ($attributes['title'] ?? ''),
            State::class => (string) ($attributes['key'] ?? ''),
            default => (string) ($attributes['name'] ?? ''),
        };

        return $description !== '' ? $description : '#'.($attributes['id'] ?? '?');
    }

    /**
     * Format a single audit.
     *
     * @return array<string, mixed>
     */
    private function formatAudit(Audit $audit): array
    {
        $class = $audit->auditable_type;
        $subjectId = (int) $audit->auditable_id;
        $subject = $this->subjects[$class][$subjectId];
        $subjectType = $this->subjectTypeKey($class);

        return [
            'id' => $audit->id,
            'created_at' => $audit->created_at?->toIso8601String(),
            'actor' => $this->formatActor($audit),
            'action' => $audit->event,
            'action_label' => self::ACTIONS[$audit->event] ?? $audit->event,
            'subject_type' => $subjectType,
            'subject_type_label' => self::SUBJECT_TYPES[$subjectType]['label'] ?? $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $this->describeSubject($class, ['id' => $subjectId, ...$subject['attributes']]),
            'subject_deleted' => $subject['deleted'],
            'changes' => $this->formatChanges($audit->old_values ?? [], $audit->new_values ?? []),
        ];
    }

    /**
     * Format the user who caused the audit.
     *
     * @return array{id: int, name: string, roles: list<string>}|null
     */
    private function formatActor(Audit $audit): ?array
    {
        if (! $audit->user_id) {
            return null;
        }

        $user = $audit->user;

        return [
            'id' => (int) $audit->user_id,
            'name' => $user instanceof User
                ? $user->firstname.' '.$user->lastname
                : $this->resolveForeignKey('user_id', $audit->user_id),
            'roles' => $user instanceof User ? $user->roles->pluck('name')->values()->all() : [],
        ];
    }

    /**
     * Format the changed fields of an audit.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return list<array{field: string, label: string, old: string|null, new: string|null}>
     */
    private function formatChanges(array $oldValues, array $newValues): array
    {
        $fields = array_unique([...array_keys($newValues), ...array_keys($oldValues)]);

        $changes = [];
        foreach ($fields as $field) {
            if ($field === 'id' || in_array($field, self::HIDDEN_FIELDS, true)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => self::FIELD_LABELS[$field] ?? $field,
                'old' => $this->formatValue($field, $oldValues[$field] ?? null),
                'new' => $this->formatValue($field, $newValues[$field] ?? null),
            ];
        }

        return $changes;
    }

    /**
     * Format a single field value for display.
     */
    private function formatValue(string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (isset(self::FOREIGN_KEYS[$field]) && is_numeric($value)) {
            return $this->resolveForeignKey($field, $value);
        }

        if (is_bool($value) || in_array($field, self::BOOLEAN_FIELDS, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Ja' : 'Nein';
        }

        if ($field === 'result' && is_string($value)) {
            return self::RESULT_LABELS[$value] ?? $value;
        }

        if ($field === 'roles' && is_array($value)) {
            return $value ? implode(', ', $value) : 'keine';
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return Str::limit((string) $value, self::MAX_VALUE_LENGTH);
    }

    /**
     * Get the filter key of an audited model class.
     */
    private function subjectTypeKey(string $class): string
    {
        foreach (self::SUBJECT_TYPES as $key => $type) {
            if ($type['class'] === $class) {
                return $key;
            }
        }

        return class_basename($class);
    }

    /**
     * Determine whether the audited class is one of the known models.
     */
    private function isKnownClass(?string $class): bool
    {
        return $class !== null && collect(self::SUBJECT_TYPES)->contains('class', $class);
    }
}
