<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        $this->admin = User::factory()->create(['firstname' => 'Zara', 'lastname' => 'Admin', 'email' => 'zara.admin@example.com']);
        $this->admin->assignRole(['admin', 'tutor']);
    }

    /**
     * Act as the admin that is logged in as tutor.
     */
    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true]);
    }

    /**
     * Get the ids of the users of the user management for the given query parameters.
     *
     * @param  array<string, string|int>  $query
     * @return array<int, int>
     */
    private function userIds(array $query = []): array
    {
        $ids = [];
        $this->actingAsAdmin()
            ->get(route('dashboard.admin.users', $query))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$ids) {
                $ids = collect($page->toArray()['props']['users']['data'])->pluck('id')->all();
            });

        return $ids;
    }

    /**
     * Get the changes of the user management.
     *
     * @param  array<int, int>  $loadedIds
     * @return array<string, mixed>
     */
    private function userChanges(int $since, array $loadedIds, array $query = []): array
    {
        return $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.users', $query + [
                'changes_since' => $since,
                'loaded_ids' => implode(',', $loadedIds),
            ]))
            ->assertOk()
            ->json();
    }

    public function test_users_are_loaded_page_by_page(): void
    {
        User::factory()->count(60)->create();

        $firstPage = $this->userIds();
        $secondPage = $this->userIds(['page' => 2]);

        $this->assertCount(50, $firstPage);
        $this->assertCount(11, $secondPage);
        $this->assertSame([], array_intersect($firstPage, $secondPage));
        $this->assertSame(User::count(), count(array_unique([...$firstPage, ...$secondPage])));
    }

    public function test_users_page_contains_scroll_metadata(): void
    {
        User::factory()->count(60)->create();

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.users'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Users')
                ->has('users.data', 50)
                ->where('users.data.0.has_pin', true)
                ->missing('users.data.0.pin')
                ->has('users.data.0._version')
            );
    }

    public function test_users_are_searched_on_the_server(): void
    {
        User::factory()->create(['firstname' => 'Maximilian', 'lastname' => 'Mustermann', 'email' => 'max@example.com']);
        User::factory()->create(['firstname' => 'Erika', 'lastname' => 'Mustermann', 'email' => 'erika@example.com']);
        User::factory()->create(['firstname' => 'Max', 'lastname' => 'Meier', 'email' => 'meier@example.com']);

        $this->assertCount(2, $this->userIds(['search' => 'mustermann']));
        $this->assertCount(2, $this->userIds(['search' => 'MAX']));
        // every term must match
        $this->assertCount(1, $this->userIds(['search' => 'max mustermann']));
        $this->assertCount(1, $this->userIds(['search' => 'erika@example']));
        $this->assertCount(0, $this->userIds(['search' => 'nobody']));
    }

    public function test_users_are_sorted_on_the_server(): void
    {
        config([
            'filesystems.disks.s3.bucket' => 'test-bucket',
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.region' => 'us-east-1',
        ]);
        $courses = Course::orderBy('abbreviation')->get();
        $anna = User::factory()->create(['firstname' => 'Anna', 'lastname' => 'Zander', 'email' => 'b@example.com', 'course_id' => $courses->last()->id, 'avatar' => 'avatars/anna.jpg']);
        $bert = User::factory()->create(['firstname' => 'Bert', 'lastname' => 'Young', 'email' => 'a@example.com', 'course_id' => $courses->first()->id]);
        $bert->assignRole('special');
        $this->admin->update(['course_id' => $courses->first()->id]);

        $this->assertSame([$anna->id, $bert->id, $this->admin->id], $this->userIds(['sort' => 'firstname']));
        $this->assertSame([$this->admin->id, $bert->id, $anna->id], $this->userIds(['sort' => 'firstname', 'direction' => 'desc']));
        $this->assertSame([$this->admin->id, $bert->id, $anna->id], $this->userIds(['sort' => 'lastname']));
        $this->assertSame([$bert->id, $anna->id, $this->admin->id], $this->userIds(['sort' => 'email']));
        $this->assertSame($anna->id, $this->userIds(['sort' => 'course', 'direction' => 'desc'])[0]);
        // users without roles first, then "admin|tutor" before "special"
        $this->assertSame([$anna->id, $this->admin->id, $bert->id], $this->userIds(['sort' => 'roles']));
        // users without avatar first
        $this->assertSame($anna->id, $this->userIds(['sort' => 'avatar'])[2]);
        $this->assertSame($anna->id, $this->userIds(['sort' => 'avatar', 'direction' => 'desc'])[0]);
    }

    public function test_invalid_sort_keys_are_ignored(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame($this->userIds(), $this->userIds(['sort' => 'password; drop table users', 'direction' => 'sideways']));
    }

    public function test_sorting_is_stable_across_pages(): void
    {
        // all users have the same first name, so the order depends on the primary key
        User::factory()->count(70)->create(['firstname' => 'Same']);
        $this->admin->update(['firstname' => 'Same']);

        $firstPage = $this->userIds(['sort' => 'firstname']);
        $secondPage = $this->userIds(['sort' => 'firstname', 'page' => 2]);

        $this->assertSame([], array_intersect($firstPage, $secondPage));
        $this->assertCount(User::count(), array_unique([...$firstPage, ...$secondPage]));
    }

    public function test_changes_contain_updated_removed_and_new_users(): void
    {
        $this->freezeSecond();
        $unchangedUser = User::factory()->create();
        $changedUser = User::factory()->create();
        $deletedUser = User::factory()->create();
        $this->travel(1)->seconds();
        $since = now()->timestamp;

        $this->travel(5)->seconds();
        $changedUser->update(['firstname' => 'Geändert']);
        $deletedUser->delete();
        $newUser = User::factory()->create();

        $changes = $this->userChanges($since, [$unchangedUser->id, $changedUser->id, $deletedUser->id]);

        $this->assertSame(now()->timestamp, $changes['time']);
        $this->assertSame([$changedUser->id], array_column($changes['rows'], 'id'));
        $this->assertSame('Geändert', $changes['rows'][0]['firstname']);
        $this->assertSame([$deletedUser->id], $changes['removed_ids']);
        $this->assertSame([$newUser->id], $changes['new_ids']);
    }

    public function test_changes_respect_the_search(): void
    {
        $this->freezeSecond();
        $matchingUser = User::factory()->create(['firstname' => 'Gesucht']);
        $otherUser = User::factory()->create(['firstname' => 'Anders']);
        $this->travel(1)->seconds();
        $since = now()->timestamp;

        $this->travel(5)->seconds();
        $matchingUser->update(['firstname' => 'Nicht mehr']);
        User::factory()->create(['firstname' => 'Gesucht Neu']);
        User::factory()->create(['firstname' => 'Anders Neu']);

        $changes = $this->userChanges($since, [$matchingUser->id], ['search' => 'gesucht']);

        // the user does not match the search anymore
        $this->assertSame([$matchingUser->id], $changes['removed_ids']);
        $this->assertCount(1, $changes['new_ids']);
        $this->assertNotContains($otherUser->id, $changes['new_ids']);
    }

    public function test_role_changes_are_detected_by_the_live_update(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $this->travel(1)->seconds();
        $since = now()->timestamp;
        $this->travel(5)->seconds();

        $this->actingAsAdmin()->post(route('dashboard.admin.editUser', $user), [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'email_confirm' => $user->email,
            'course_id' => $user->course_id,
            'role_id' => [DB::table('roles')->where('name', 'tutor')->value('id')],
            'is_disabled' => false,
        ])->assertSessionHasNoErrors();

        $changes = $this->userChanges($since, [$user->id]);

        $this->assertSame(['tutor'], array_column($changes['rows'][0]['roles'], 'name'));
    }

    public function test_user_management_requires_permission(): void
    {
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->getJson(route('dashboard.admin.users', ['changes_since' => 0, 'loaded_ids' => $this->admin->id]))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_registrations_are_scoped_searched_and_sorted(): void
    {
        $event = Event::factory()->create(['type' => 'slot_booking', 'consider_alcohol' => true]);
        $otherEvent = Event::factory()->create();
        $slotA = Slot::factory()->create(['event_id' => $event->id, 'name' => 'A-Slot']);
        $slotB = Slot::factory()->create(['event_id' => $event->id, 'name' => 'B-Slot']);

        $anna = User::factory()->create(['firstname' => 'Anna', 'lastname' => 'Zander']);
        $bert = User::factory()->create(['firstname' => 'Bert', 'lastname' => 'Young']);
        $registrationAnna = Registration::factory()->create(['event_id' => $event->id, 'user_id' => $anna->id, 'slot_id' => $slotB->id, 'is_present' => false, 'queue_position' => null, 'form_responses' => ['b']]);
        $registrationBert = Registration::factory()->create(['event_id' => $event->id, 'user_id' => $bert->id, 'slot_id' => $slotA->id, 'is_present' => true, 'queue_position' => 3, 'form_responses' => ['a']]);
        Registration::factory()->create(['event_id' => $otherEvent->id, 'user_id' => $anna->id]);

        $registrationIds = function (array $query) use ($event): array {
            $ids = [];
            $this->actingAsAdmin()
                ->get(route('dashboard.admin.event.registrations', ['event' => $event->id] + $query))
                ->assertOk()
                ->assertInertia(function (Assert $page) use (&$ids) {
                    $ids = collect($page->toArray()['props']['registrations']['data'])->pluck('id')->all();
                });

            return $ids;
        };

        $this->assertCount(2, $registrationIds([]));
        $this->assertSame([$registrationBert->id], $registrationIds(['search' => 'young']));
        $this->assertSame([$registrationAnna->id, $registrationBert->id], $registrationIds(['sort' => 'firstname']));
        $this->assertSame([$registrationBert->id, $registrationAnna->id], $registrationIds(['sort' => 'lastname']));
        $this->assertSame([$registrationBert->id, $registrationAnna->id], $registrationIds(['sort' => 'slot']));
        $this->assertSame([$registrationAnna->id, $registrationBert->id], $registrationIds(['sort' => 'queue_position']));
        $this->assertSame([$registrationBert->id, $registrationAnna->id], $registrationIds(['sort' => 'queue_position', 'direction' => 'desc']));
        // present registrations first
        $this->assertSame([$registrationBert->id, $registrationAnna->id], $registrationIds(['sort' => 'is_present']));
        $this->assertSame([$registrationBert->id, $registrationAnna->id], $registrationIds(['sort' => 'form_responses']));

        // every sort key works in both directions
        foreach (['course', 'group', 'drinks_alcohol', 'fulfils_requirements'] as $sortKey) {
            foreach (['asc', 'desc'] as $direction) {
                $this->assertCount(2, $registrationIds(['sort' => $sortKey, 'direction' => $direction]));
            }
        }
    }

    public function test_registration_changes_contain_changed_users_and_cascaded_deletions(): void
    {
        $this->freezeSecond();
        $event = Event::factory()->create(['type' => 'event_registration']);
        $renamedUser = User::factory()->create();
        $deletedUser = User::factory()->create();
        $renamedRegistration = Registration::factory()->create(['event_id' => $event->id, 'user_id' => $renamedUser->id]);
        $deletedRegistration = Registration::factory()->create(['event_id' => $event->id, 'user_id' => $deletedUser->id]);
        $this->travel(1)->seconds();
        $since = now()->timestamp;

        $this->travel(5)->seconds();
        $renamedUser->update(['firstname' => 'Neuer Name']);
        // the registration is deleted by the foreign key (cascade)
        $deletedUser->delete();

        $changes = $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.event.registrations', [
                'event' => $event->id,
                'changes_since' => $since,
                'loaded_ids' => $renamedRegistration->id.','.$deletedRegistration->id,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame([$renamedRegistration->id], array_column($changes['rows'], 'id'));
        $this->assertSame('Neuer Name', $changes['rows'][0]['user']['firstname']);
        $this->assertSame([$deletedRegistration->id], $changes['removed_ids']);
    }

    public function test_tutor_slot_and_group_pages_only_contain_their_registrations(): void
    {
        $slotEvent = Event::factory()->create(['type' => 'slot_booking']);
        $slot = Slot::factory()->create(['event_id' => $slotEvent->id]);
        $otherSlot = Slot::factory()->create(['event_id' => $slotEvent->id]);
        $groupEvent = Event::factory()->create(['type' => 'group_phase']);
        $group = Group::create(['event_id' => $groupEvent->id, 'name' => 'Gruppe']);

        $users = User::factory()->count(3)->create();
        $slotRegistration = Registration::factory()->create(['event_id' => $slotEvent->id, 'user_id' => $users[0]->id, 'slot_id' => $slot->id]);
        Registration::factory()->create(['event_id' => $slotEvent->id, 'user_id' => $users[1]->id, 'slot_id' => $otherSlot->id]);
        $groupRegistration = Registration::factory()->create(['event_id' => $groupEvent->id, 'user_id' => $users[2]->id, 'group_id' => $group->id]);

        $this->actingAsAdmin()
            ->get(route('dashboard.tutor.slot.index', $slot))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Tutor/Slot')
                ->has('registrations.data', 1)
                ->where('registrations.data.0.id', $slotRegistration->id)
            );

        $this->actingAsAdmin()
            ->get(route('dashboard.tutor.group.index', $group))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Tutor/Group')
                ->has('registrations.data', 1)
                ->where('registrations.data.0.id', $groupRegistration->id)
            );
    }

    public function test_event_pages_contain_the_table_of_the_event_type(): void
    {
        $groupEvent = Event::factory()->create(['type' => 'group_phase']);
        $group = Group::create(['event_id' => $groupEvent->id, 'name' => 'Gruppe 1']);
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            Registration::factory()->create(['event_id' => $groupEvent->id, 'user_id' => $user->id, 'group_id' => $group->id]);
        }

        $slotEvent = Event::factory()->create(['type' => 'slot_booking']);
        Slot::factory()->create(['event_id' => $slotEvent->id, 'maximum_participants' => 5]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.event.index', $groupEvent))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Event')
                ->has('groups.data', 1)
                ->where('groups.data.0.registrations_count', 2)
                ->missing('slots')
                ->missing('registrations')
            );

        $this->actingAsAdmin()
            ->get(route('dashboard.tutor.event.index', $slotEvent))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Tutor/Event')
                ->has('slots.data', 1)
                ->where('slots.data.0.registrations_count', 0)
            );

        // the amount of registrations is refreshed by the live update for every loaded row
        $changes = $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.event.index', ['event' => $groupEvent->id, 'changes_since' => now()->addHour()->timestamp, 'loaded_ids' => $group->id]))
            ->json();
        $this->assertSame(2, $changes['rows'][0]['registrations_count']);
    }

    public function test_small_tables_can_be_sorted_by_every_key(): void
    {
        $event = Event::factory()->create(['type' => 'group_phase']);
        $event->courses()->attach(Course::first());
        Group::create(['event_id' => $event->id, 'name' => 'Gruppe 1']);
        Group::create(['event_id' => $event->id, 'name' => 'Gruppe 2']);
        $slotEvent = Event::factory()->create(['type' => 'slot_booking']);
        Slot::factory()->count(2)->create(['event_id' => $slotEvent->id]);

        foreach (['name', 'type', 'consider_alcohol', 'courses', 'has_requirements', 'registration_from', 'registrations_count'] as $sortKey) {
            $this->actingAsAdmin()
                ->get(route('dashboard.tutor.index', ['sort' => $sortKey, 'direction' => 'desc']))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('events.data', 2));
        }

        foreach (['name', 'courses', 'registrations_count'] as $sortKey) {
            $this->actingAsAdmin()
                ->get(route('dashboard.tutor.event.index', ['event' => $event->id, 'sort' => $sortKey]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('groups.data', 2));
        }

        foreach (['name', 'has_requirements', 'registrations_count'] as $sortKey) {
            $this->actingAsAdmin()
                ->get(route('dashboard.tutor.event.index', ['event' => $slotEvent->id, 'sort' => $sortKey]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('slots.data', 2));
        }
    }

    public function test_partial_reloads_do_not_query_the_shared_props(): void
    {
        User::factory()->count(60)->create();

        $response = $this->actingAsAdmin()->get(route('dashboard.admin.users', ['page' => 2]), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Dashboard/Admin/Users',
            'X-Inertia-Partial-Data' => 'users',
        ]);

        $props = $response->assertOk()->json('props');
        $this->assertArrayHasKey('users', $props);
        $this->assertArrayNotHasKey('modules', $props);
        $this->assertArrayNotHasKey('pages', $props);
        $this->assertArrayNotHasKey('user', $props);
    }

    public function test_registration_amount_endpoints_count_by_course(): void
    {
        $event = Event::factory()->create(['type' => 'slot_booking']);
        $slot = Slot::factory()->create(['event_id' => $event->id]);
        [$courseA, $courseB] = Course::take(2)->get();
        $users = collect([
            User::factory()->create(['course_id' => $courseA->id]),
            User::factory()->create(['course_id' => $courseA->id]),
            User::factory()->create(['course_id' => $courseB->id]),
        ]);
        foreach ($users as $user) {
            Registration::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'slot_id' => $slot->id]);
        }

        $this->actingAsAdmin()
            ->getJson(route('api.event.registrationsAmount', $event))
            ->assertOk()
            ->assertJsonPath('amount', 3)
            ->assertJsonPath("slots.{$slot->id}", 3)
            ->assertJsonPath("courses.{$courseA->id}", 2)
            ->assertJsonPath("courses.{$courseB->id}", 1);

        $events = $this->actingAsAdmin()->getJson(route('api.events.registrationsAmount'))->assertOk()->json();
        $this->assertSame(3, $events[0]['amount']);
        $this->assertSame(2, $events[0]['courses'][$courseA->id]);
    }
}
