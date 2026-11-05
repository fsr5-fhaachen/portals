<?php

namespace Tests\Feature;

use App\Helpers\WorkshopDivision;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopAssignment;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Database\Seeders\CourseSeeder;
use Database\Seeders\EventsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopDivisionTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CourseSeeder::class);

        $this->event = Event::factory()->workshopBooking(5)->create();
    }

    /**
     * Create blocks that start one after another.
     *
     * @return list<WorkshopBlock>
     */
    private function createBlocks(int $count): array
    {
        $blocks = [];
        for ($i = 0; $i < $count; $i++) {
            $blocks[] = WorkshopBlock::factory()->for($this->event)->create([
                'name' => 'Block '.($i + 1),
                'starts_at' => now()->addDay()->setTime(10 + $i * 2, 0),
            ]);
        }

        return $blocks;
    }

    /**
     * Create a workshop with a session in every given block.
     *
     * @param  list<WorkshopBlock>  $blocks
     */
    private function createWorkshop(string $name, array $blocks, ?int $maximum = null, int $minimum = 0): Workshop
    {
        $workshop = Workshop::factory()->for($this->event)->create(['name' => $name]);

        foreach ($blocks as $block) {
            WorkshopSession::factory()->for($workshop)->for($block, 'block')->create([
                'minimum_participants' => $minimum,
                'maximum_participants' => $maximum,
            ]);
        }

        return $workshop;
    }

    /**
     * Register a new user with the given workshops as priorities.
     *
     * @param  list<Workshop>  $workshops
     */
    private function register(array $workshops): Registration
    {
        $registration = $this->event->registrations()->create(['user_id' => User::factory()->create()->id]);

        foreach ($workshops as $index => $workshop) {
            $registration->workshopPriorities()->create(['workshop_id' => $workshop->id, 'priority' => $index + 1]);
        }

        return $registration;
    }

    /**
     * Get the assigned sessions of a registration by block id.
     *
     * @return array<int, WorkshopSession>
     */
    private function sessionsOf(Registration $registration): array
    {
        return $registration->workshopAssignments()->with('session')->get()
            ->mapWithKeys(fn (WorkshopAssignment $assignment) => [$assignment->workshop_block_id => $assignment->session])
            ->all();
    }

    /**
     * Get the ids of the assigned workshops of a registration.
     *
     * @return list<int>
     */
    private function workshopIdsOf(Registration $registration): array
    {
        return collect($this->sessionsOf($registration))->pluck('workshop_id')->sort()->values()->all();
    }

    public function test_everyone_gets_the_wishes_if_there_are_enough_places(): void
    {
        $blocks = $this->createBlocks(2);
        $a = $this->createWorkshop('A', $blocks);
        $b = $this->createWorkshop('B', $blocks);
        $c = $this->createWorkshop('C', $blocks);

        $registrations = [$this->register([$a, $b, $c]), $this->register([$c, $a, $b])];

        $summary = (new WorkshopDivision($this->event, 1))->assign();

        $this->assertSame([$a->id, $b->id], $this->workshopIdsOf($registrations[0]));
        $this->assertSame([$a->id, $c->id], $this->workshopIdsOf($registrations[1]));
        $this->assertSame([1 => 2, 2 => 2], $summary['priorities']);
        $this->assertSame(0, $summary['unassigned']);
    }

    public function test_every_user_gets_at_most_one_workshop_per_block_and_no_workshop_twice(): void
    {
        $blocks = $this->createBlocks(3);
        $workshops = collect(range(1, 5))->map(fn (int $i) => $this->createWorkshop("W{$i}", $blocks, 4))->all();

        $registrations = collect(range(1, 12))->map(fn () => $this->register(collect($workshops)->shuffle()->all()));

        (new WorkshopDivision($this->event, 7))->assign();

        foreach ($registrations as $registration) {
            $assignments = $registration->workshopAssignments()->with('session')->get();

            $this->assertSame($assignments->count(), $assignments->pluck('workshop_block_id')->unique()->count());
            $this->assertSame($assignments->count(), $assignments->pluck('session.workshop_id')->unique()->count());
            $this->assertTrue($assignments->every(fn (WorkshopAssignment $assignment) => $assignment->session->workshop_block_id === $assignment->workshop_block_id));
        }

        WorkshopSession::withCount('assignments')->get()->each(fn (WorkshopSession $session) => $this->assertLessThanOrEqual(4, $session->assignments_count));
    }

    public function test_higher_priorities_are_fulfilled_first_on_a_shortage(): void
    {
        $blocks = $this->createBlocks(1);
        $popular = $this->createWorkshop('Popular', $blocks, 1);
        $other = $this->createWorkshop('Other', $blocks);

        $first = $this->register([$popular, $other]);
        $second = $this->register([$other, $popular]);

        foreach (range(1, 10) as $seed) {
            (new WorkshopDivision($this->event, $seed))->assign();

            // the second user would also take the popular workshop, but only as priority 2
            $this->assertSame([$popular->id], $this->workshopIdsOf($first));
            $this->assertSame([$other->id], $this->workshopIdsOf($second));
        }
    }

    public function test_earlier_sessions_are_filled_first(): void
    {
        $blocks = $this->createBlocks(2);
        $a = $this->createWorkshop('A', $blocks, 2);
        $b = $this->createWorkshop('B', $blocks, 2);

        $registrations = collect(range(1, 3))->map(fn () => $this->register([$a]));

        (new WorkshopDivision($this->event, 3))->assign();

        $earlySession = $a->sessions()->where('workshop_block_id', $blocks[0]->id)->first();
        $lateSession = $a->sessions()->where('workshop_block_id', $blocks[1]->id)->first();

        $this->assertSame(2, $earlySession->assignments()->count());
        $this->assertSame(1, $lateSession->assignments()->count());
        $this->assertSame(0, $b->sessions()->withCount('assignments')->get()->sum('assignments_count'));
        $this->assertCount(3, $registrations->filter(fn (Registration $registration) => count($this->sessionsOf($registration)) === 1));
    }

    public function test_assignments_move_to_another_block_to_make_room_for_a_wish(): void
    {
        $blocks = $this->createBlocks(2);
        $a = $this->createWorkshop('A', $blocks);
        // B only takes place in the first block
        $b = $this->createWorkshop('B', [$blocks[0]]);

        $registration = $this->register([$a, $b]);

        (new WorkshopDivision($this->event, 1))->assign();

        $sessions = $this->sessionsOf($registration);
        $this->assertSame($b->id, $sessions[$blocks[0]->id]->workshop_id);
        $this->assertSame($a->id, $sessions[$blocks[1]->id]->workshop_id);
    }

    public function test_session_below_its_minimum_is_cancelled_and_its_participants_are_distributed_again(): void
    {
        $blocks = $this->createBlocks(1);
        $small = $this->createWorkshop('Small', $blocks, null, 3);
        $fallback = $this->createWorkshop('Fallback', $blocks);

        $registrations = collect(range(1, 2))->map(fn () => $this->register([$small, $fallback]));

        $summary = (new WorkshopDivision($this->event, 1))->assign();

        $this->assertTrue($small->sessions()->first()->is_cancelled);
        $this->assertFalse($fallback->sessions()->first()->is_cancelled);
        $registrations->each(fn (Registration $registration) => $this->assertSame([$fallback->id], $this->workshopIdsOf($registration)));
        $this->assertSame([2 => 2], $summary['priorities']);
        $this->assertSame(1, $summary['cancelled_sessions']);
    }

    public function test_later_session_below_its_minimum_is_cancelled_and_the_earlier_one_is_filled(): void
    {
        $blocks = $this->createBlocks(2);
        $a = $this->createWorkshop('A', $blocks, 4, 2);

        collect(range(1, 5))->each(fn () => $this->register([$a]));

        (new WorkshopDivision($this->event, 1))->assign();

        $earlySession = $a->sessions()->where('workshop_block_id', $blocks[0]->id)->first();
        $lateSession = $a->sessions()->where('workshop_block_id', $blocks[1]->id)->first();

        // 4 + 1 would leave the later session below its minimum of 2
        $this->assertSame(4, $earlySession->assignments()->count());
        $this->assertTrue($lateSession->fresh()->is_cancelled);
        $this->assertSame(0, $lateSession->assignments()->count());
    }

    public function test_users_without_a_possible_wish_stay_unassigned(): void
    {
        $blocks = $this->createBlocks(2);
        $a = $this->createWorkshop('A', [$blocks[0]], 1);

        $first = $this->register([$a]);
        $second = $this->register([$a]);

        $summary = (new WorkshopDivision($this->event, 1))->assign();

        $this->assertSame(1, count($this->sessionsOf($first)) + count($this->sessionsOf($second)));
        $this->assertSame([1 => 1], $summary['priorities']);
        $this->assertSame(3, $summary['unassigned']);
    }

    public function test_session_without_participants_and_with_minimum_is_cancelled(): void
    {
        $blocks = $this->createBlocks(1);
        $empty = $this->createWorkshop('Empty', $blocks, null, 1);

        (new WorkshopDivision($this->event, 1))->assign();

        $this->assertTrue($empty->sessions()->first()->is_cancelled);
    }

    public function test_assignment_replaces_the_previous_assignment_and_is_reproducible_with_a_seed(): void
    {
        $blocks = $this->createBlocks(2);
        $workshops = collect(range(1, 4))->map(fn (int $i) => $this->createWorkshop("W{$i}", $blocks, 3))->all();
        $registrations = collect(range(1, 10))->map(fn (int $i) => $this->register([$workshops[$i % 4], $workshops[($i + 1) % 4], $workshops[($i + 2) % 4]]));

        (new WorkshopDivision($this->event, 42))->assign();
        $first = $registrations->map(fn (Registration $registration) => $this->workshopIdsOf($registration))->all();
        $assignmentCount = WorkshopAssignment::count();

        (new WorkshopDivision($this->event, 42))->assign();
        $second = $registrations->map(fn (Registration $registration) => $this->workshopIdsOf($registration))->all();

        $this->assertSame($first, $second);
        $this->assertSame($assignmentCount, WorkshopAssignment::count());
    }

    public function test_registrations_without_wishes_keep_their_workshops_and_take_up_the_places(): void
    {
        $blocks = $this->createBlocks(1);
        $a = $this->createWorkshop('A', $blocks, 1);
        $b = $this->createWorkshop('B', $blocks);

        $placedByHand = $this->register([]);
        $placedAssignment = WorkshopAssignment::create([
            'registration_id' => $placedByHand->id,
            'workshop_session_id' => $a->sessions()->sole()->id,
            'workshop_block_id' => $blocks[0]->id,
        ]);
        $registration = $this->register([$a, $b]);

        $summary = (new WorkshopDivision($this->event, 1))->assign();

        $this->assertModelExists($placedAssignment);
        $this->assertSame([$b->id], $this->workshopIdsOf($registration));
        $this->assertSame([2 => 1], $summary['priorities']);
        $this->assertSame(0, $summary['unassigned']);
    }

    public function test_session_with_registrations_without_wishes_is_not_cancelled(): void
    {
        $blocks = $this->createBlocks(1);
        $a = $this->createWorkshop('A', $blocks, null, 3);

        $placedAssignment = WorkshopAssignment::create([
            'registration_id' => $this->register([])->id,
            'workshop_session_id' => $a->sessions()->sole()->id,
            'workshop_block_id' => $blocks[0]->id,
        ]);

        $summary = (new WorkshopDivision($this->event, 1))->assign();

        $this->assertFalse($a->sessions()->sole()->is_cancelled);
        $this->assertModelExists($placedAssignment);
        $this->assertSame(0, $summary['unassigned']);
        $this->assertSame(0, $summary['cancelled_sessions']);
    }

    public function test_division_can_assign_again_after_a_registration_was_deleted(): void
    {
        $blocks = $this->createBlocks(1);
        $a = $this->createWorkshop('A', $blocks);
        $first = $this->register([$a]);
        $second = $this->register([$a]);

        $division = new WorkshopDivision($this->event, 1);
        $division->assign();
        $first->delete();
        $summary = $division->assign();

        $this->assertSame([$a->id], $this->workshopIdsOf($second));
        $this->assertSame([1 => 1], $summary['priorities']);
        $this->assertSame(1, WorkshopAssignment::count());
    }

    public function test_assignments_of_other_events_are_kept(): void
    {
        $otherEvent = Event::factory()->workshopBooking()->create();
        $otherSession = WorkshopSession::factory()
            ->for(Workshop::factory()->for($otherEvent))
            ->for(WorkshopBlock::factory()->for($otherEvent), 'block')
            ->create();
        $otherAssignment = WorkshopAssignment::create([
            'registration_id' => $otherEvent->registrations()->create(['user_id' => User::factory()->create()->id])->id,
            'workshop_session_id' => $otherSession->id,
            'workshop_block_id' => $otherSession->workshop_block_id,
        ]);

        $blocks = $this->createBlocks(1);
        $this->register([$this->createWorkshop('A', $blocks)]);

        (new WorkshopDivision($this->event, 1))->assign();

        $this->assertModelExists($otherAssignment);
    }

    public function test_demo_workshop_event_can_be_assigned(): void
    {
        User::factory()->count(40)->create();

        (new EventsDemoSeeder)->runWorkshopBooking();

        $event = Event::where('name', 'workshop_booking')->sole();
        $summary = (new WorkshopDivision($event, 1))->assign();

        $this->assertSame(40, $event->registrations()->count());
        $this->assertSame(40 * 3, array_sum($summary['priorities']) + $summary['unassigned']);

        // every session that takes place has enough participants and not too many
        $event->workshopSessions()->withCount('assignments')->get()
            ->reject(fn (WorkshopSession $session) => $session->is_cancelled)
            ->each(function (WorkshopSession $session) {
                $this->assertGreaterThanOrEqual($session->minimum_participants, $session->assignments_count);
                $this->assertLessThanOrEqual($session->maximum_participants ?? PHP_INT_MAX, $session->assignments_count);
            });
    }
}
