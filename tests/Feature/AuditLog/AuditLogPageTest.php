<?php

namespace Tests\Feature\AuditLog;

use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->admin = $this->createUserWithRoles('admin');
    }

    /**
     * Create an audit row by hand.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeAudit(array $attributes): Audit
    {
        $auditable = $attributes['auditable'] ?? null;
        $user = array_key_exists('user', $attributes) ? $attributes['user'] : $this->admin;
        unset($attributes['auditable'], $attributes['user']);

        return Audit::create([
            'user_type' => $user ? User::class : null,
            'user_id' => $user?->id,
            'event' => 'updated',
            'auditable_type' => $auditable ? $auditable::class : Event::class,
            'auditable_id' => $auditable?->id ?? 999,
            'old_values' => [],
            'new_values' => [],
            ...$attributes,
        ]);
    }

    /**
     * Get the formatted audits of the audit log page.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function auditLogEntries(array $query = []): array
    {
        return $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.auditLog.index', $query))
            ->assertOk()
            ->inertiaProps('audits.data');
    }

    public function test_admin_can_view_the_audit_log(): void
    {
        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.auditLog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/AuditLog')
                ->has('audits.data')
                ->has('users')
                ->has('subjectTypes')
                ->has('actions')
                ->where('filters.staff_only', true)
            );
    }

    public function test_tutor_is_redirected(): void
    {
        $tutor = $this->createUserWithRoles('tutor');

        $this->actingAsTutor($tutor)
            ->get(route('dashboard.admin.auditLog.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_esa_is_redirected(): void
    {
        $esa = $this->createUserWithRoles('esa');

        $this->actingAsTutor($esa)
            ->get(route('dashboard.admin.auditLog.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        Role::findByName('admin')->revokePermissionTo('view audit log');

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.auditLog.index'))
            ->assertForbidden();
    }

    public function test_audits_are_sorted_newest_first(): void
    {
        $older = $this->makeAudit(['event' => 'created']);
        $newer = $this->makeAudit(['event' => 'deleted']);

        $entries = $this->auditLogEntries();

        $this->assertSame([$newer->id, $older->id], array_column($entries, 'id'));
    }

    public function test_audits_can_be_filtered_by_user(): void
    {
        $tutor = $this->createUserWithRoles('tutor');
        $this->makeAudit([]);
        $tutorAudit = $this->makeAudit(['user' => $tutor]);

        $entries = $this->auditLogEntries(['user_id' => $tutor->id]);

        $this->assertSame([$tutorAudit->id], array_column($entries, 'id'));
        $this->assertSame($tutor->firstname.' '.$tutor->lastname, $entries[0]['actor']['name']);
        $this->assertSame(['tutor'], $entries[0]['actor']['roles']);
    }

    public function test_audits_can_be_filtered_by_subject(): void
    {
        $event = Event::factory()->create();
        $group = Group::factory()->create();
        $eventAudit = $this->makeAudit(['auditable' => $event]);
        $groupAudit = $this->makeAudit(['auditable' => $group]);
        $otherGroupAudit = $this->makeAudit(['auditable' => Group::factory()->create()]);

        $this->assertSame([$eventAudit->id], array_column($this->auditLogEntries(['subject_type' => 'Event']), 'id'));
        $this->assertSame([$otherGroupAudit->id, $groupAudit->id], array_column($this->auditLogEntries(['subject_type' => 'Group']), 'id'));
        $this->assertSame([$groupAudit->id], array_column($this->auditLogEntries(['subject_type' => 'Group', 'subject_id' => $group->id]), 'id'));
    }

    public function test_audits_can_be_filtered_by_action(): void
    {
        $this->makeAudit(['event' => 'created']);
        $deleted = $this->makeAudit(['event' => 'deleted']);

        $this->assertSame([$deleted->id], array_column($this->auditLogEntries(['action' => 'deleted']), 'id'));
    }

    public function test_audits_can_be_filtered_by_date_range(): void
    {
        $this->makeAudit(['created_at' => Carbon::parse('2026-09-01 12:00:00')]);
        $inRange = $this->makeAudit(['created_at' => Carbon::parse('2026-09-02 23:59:00')]);
        $this->makeAudit(['created_at' => Carbon::parse('2026-09-03 00:00:01')]);

        $entries = $this->auditLogEntries(['from' => '2026-09-02', 'to' => '2026-09-02']);

        $this->assertSame([$inRange->id], array_column($entries, 'id'));
    }

    public function test_student_actions_are_hidden_by_default(): void
    {
        $student = $this->createUserWithRoles();
        $staffAudit = $this->makeAudit([]);
        $systemAudit = $this->makeAudit(['user' => null]);
        $studentAudit = $this->makeAudit(['user' => $student]);

        $this->assertSame([$systemAudit->id, $staffAudit->id], array_column($this->auditLogEntries(), 'id'));
        $this->assertSame(
            [$studentAudit->id, $systemAudit->id, $staffAudit->id],
            array_column($this->auditLogEntries(['staff_only' => '0']), 'id')
        );
    }

    public function test_invalid_filters_are_ignored(): void
    {
        $audit = $this->makeAudit([]);

        $response = $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.auditLog.index', [
                'user_id' => 'abc',
                'subject_type' => 'Foo',
                'subject_id' => '-3',
                'action' => 'hacked',
                'from' => '2026-02-31',
                'to' => 'yesterday',
                'staff_only' => 'maybe',
            ]))
            ->assertOk();

        $this->assertSame([$audit->id], array_column($response->inertiaProps('audits.data'), 'id'));
        $this->assertSame([
            'user_id' => null,
            'subject_type' => null,
            'subject_id' => null,
            'action' => null,
            'from' => null,
            'to' => null,
            'staff_only' => true,
        ], $response->inertiaProps('filters'));
    }

    public function test_audits_are_paginated(): void
    {
        for ($i = 0; $i < 51; $i++) {
            $this->makeAudit([]);
        }

        $firstPage = $this->actingAsTutor($this->admin)->get(route('dashboard.admin.auditLog.index'));
        $this->assertCount(50, $firstPage->inertiaProps('audits.data'));
        $this->assertStringContainsString('page=2', $firstPage->inertiaProps('audits.next_page_url'));

        $secondPage = $this->actingAsTutor($this->admin)->get(route('dashboard.admin.auditLog.index', ['page' => 2, 'action' => 'updated']));
        $this->assertCount(1, $secondPage->inertiaProps('audits.data'));
        $this->assertStringContainsString('action=updated', $secondPage->inertiaProps('audits.prev_page_url'));
    }

    public function test_entries_have_labels_and_resolved_names(): void
    {
        $student = $this->createUserWithRoles();
        $event = Event::factory()->create(['name' => 'Kneipentour']);
        $registration = Registration::create(['event_id' => $event->id, 'user_id' => $student->id]);
        $this->makeAudit([
            'auditable' => $registration,
            'old_values' => ['is_present' => 0, 'user_id' => $student->id],
            'new_values' => ['is_present' => 1, 'user_id' => $student->id],
        ]);

        $entry = $this->auditLogEntries()[0];

        $this->assertSame('Geändert', $entry['action_label']);
        $this->assertSame('Registration', $entry['subject_type']);
        $this->assertSame('Anmeldung', $entry['subject_type_label']);
        $this->assertSame($student->firstname.' '.$student->lastname.' · Kneipentour', $entry['subject_label']);
        $this->assertFalse($entry['subject_deleted']);
        $this->assertSame([
            ['field' => 'is_present', 'label' => 'Anwesend', 'old' => 'Nein', 'new' => 'Ja'],
            ['field' => 'user_id', 'label' => 'Nutzer', 'old' => $student->firstname.' '.$student->lastname, 'new' => $student->firstname.' '.$student->lastname],
        ], $entry['changes']);
    }

    public function test_deleted_records_are_named_from_their_last_audit(): void
    {
        $group = Group::factory()->create(['name' => 'Gruppe 7']);
        $groupId = $group->id;
        $group->delete();
        $this->makeAudit([
            'event' => 'deleted',
            'auditable_type' => Group::class,
            'auditable_id' => $groupId,
            'old_values' => ['id' => $groupId, 'name' => 'Gruppe 7'],
        ]);
        $this->makeAudit([
            'auditable' => Registration::create(['event_id' => Event::factory()->create()->id, 'user_id' => $this->admin->id]),
            'old_values' => ['group_id' => $groupId],
            'new_values' => ['group_id' => null],
        ]);

        $entries = $this->auditLogEntries();

        $this->assertSame('Gruppe 7 (gelöscht)', $entries[0]['changes'][0]['old']);
        $this->assertNull($entries[0]['changes'][0]['new']);
        $this->assertSame('Gruppe 7', $entries[1]['subject_label']);
        $this->assertTrue($entries[1]['subject_deleted']);
    }

    public function test_role_changes_and_logins_are_labelled(): void
    {
        $tutor = $this->createUserWithRoles('tutor');
        $this->makeAudit(['event' => 'rolesUpdated', 'auditable' => $tutor, 'old_values' => ['roles' => []], 'new_values' => ['roles' => ['esa', 'tutor']]]);
        $this->makeAudit(['event' => 'tutorLoginFailed', 'auditable' => $tutor, 'user' => $tutor]);

        $entries = $this->auditLogEntries();

        $this->assertSame('Tutor-Login fehlgeschlagen', $entries[0]['action_label']);
        $this->assertSame([], $entries[0]['changes']);
        $this->assertSame('Rollen geändert', $entries[1]['action_label']);
        $this->assertSame([['field' => 'roles', 'label' => 'Rollen', 'old' => 'keine', 'new' => 'esa, tutor']], $entries[1]['changes']);
    }

    public function test_sensitive_fields_are_never_shown(): void
    {
        $this->makeAudit([
            'auditable' => $this->admin,
            'old_values' => ['remember_token' => 'old-secret-token', 'pin' => 1234, 'password' => 'hunter2', 'firstname' => 'Alt'],
            'new_values' => ['remember_token' => 'new-secret-token', 'pin' => 4321, 'password' => 'hunter3', 'firstname' => 'Neu'],
        ]);

        $response = $this->actingAsTutor($this->admin)->get(route('dashboard.admin.auditLog.index'));

        $this->assertSame(['firstname'], array_column($response->inertiaProps('audits.data.0.changes'), 'field'));
        foreach (['old-secret-token', 'new-secret-token', 'hunter2', 'hunter3', '4321'] as $secret) {
            $response->assertDontSee($secret, false);
        }
    }

    public function test_query_count_does_not_grow_with_the_number_of_audits(): void
    {
        $createAudits = function (int $count): void {
            for ($i = 0; $i < $count; $i++) {
                $student = $this->createUserWithRoles();
                $group = Group::factory()->create();
                $registration = Registration::create(['event_id' => $group->event_id, 'user_id' => $student->id, 'group_id' => $group->id]);
                $this->makeAudit(['auditable' => $registration, 'new_values' => ['group_id' => $group->id, 'slot_id' => null]]);
                $this->makeAudit(['auditable' => $group, 'new_values' => ['event_id' => $group->event_id]]);
                $this->makeAudit(['auditable' => $student, 'user' => $this->createUserWithRoles('tutor'), 'new_values' => ['course_id' => $student->course_id]]);
            }
        };
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAsTutor($this->admin)->get(route('dashboard.admin.auditLog.index'))->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        // warm up the permission cache
        $this->actingAsTutor($this->admin)->get(route('dashboard.admin.auditLog.index'))->assertOk();

        $createAudits(2);
        $fewAuditsQueries = $countQueries();

        $createAudits(10);
        $manyAuditsQueries = $countQueries();

        $this->assertSame($fewAuditsQueries, $manyAuditsQueries);
    }
}
