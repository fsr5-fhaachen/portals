<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class DashboardAdminStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(['admin', 'tutor']);
    }

    /**
     * Download the statistics as admin and return the raw csv.
     */
    private function downloadStatistics(): string
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true])
            ->get(route('dashboard.admin.downloadStatistics'))
            ->assertOk()
            ->streamedContent();
    }

    /**
     * Parse the csv into rows of cells.
     *
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array
    {
        $csv = str_starts_with($csv, "\xEF\xBB\xBF") ? substr($csv, 3) : $csv;
        $lines = explode("\n", trim($csv));

        return array_map(fn (string $line) => str_getcsv($line, ';'), $lines);
    }

    /**
     * Build an expected row: the label, the ten statistic cells and the count per course (0 for missing courses).
     *
     * @param  array<int, string>  $statistics
     * @param  array<string, int>  $courseCounts  count by course abbreviation
     * @return array<int, string>
     */
    private function expectedRow(string $label, array $statistics, array $courseCounts = []): array
    {
        $courseCells = Course::all()->map(fn (Course $course) => (string) ($courseCounts[$course->abbreviation] ?? 0))->all();

        return [$label, ...$statistics, ...$courseCells];
    }

    /**
     * Create a user of the given course without roles.
     */
    private function student(string $courseAbbreviation): User
    {
        return User::factory()->create(['course_id' => Course::where('abbreviation', $courseAbbreviation)->sole()->id]);
    }

    /**
     * Create an event with fixed settings.
     */
    private function event(string $name, string $type, bool $hasRequirements, bool $considerAlcohol): Event
    {
        return Event::factory()->create([
            'name' => $name,
            'type' => $type,
            'has_requirements' => $hasRequirements,
            'consider_alcohol' => $considerAlcohol,
        ]);
    }

    /**
     * Register a user for an event with fixed values.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function register(Event $event, User $user, bool $isPresent, bool $fulfilsRequirements, bool $drinksAlcohol, array $attributes = []): Registration
    {
        return Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'is_present' => $isPresent,
            'fulfils_requirements' => $fulfilsRequirements,
            'drinks_alcohol' => $drinksAlcohol,
            ...$attributes,
        ]);
    }

    public function test_statistics_contain_global_event_group_and_slot_rows(): void
    {
        $inf1 = $this->student('INF');
        $inf2 = $this->student('INF');
        $inf3 = $this->student('INF');
        $et = $this->student('ET');

        // event registration with requirements, without alcohol
        $rally = $this->event('Rally', 'event_registration', true, false);
        $this->register($rally, $inf1, true, true, false);
        $this->register($rally, $inf2, false, false, true);
        $this->register($rally, $inf3, true, true, false);

        // group phase without requirements, with alcohol
        $groupPhase = $this->event('Group Phase', 'group_phase', false, true);
        $alpha = Group::factory()->create(['event_id' => $groupPhase->id, 'name' => 'Alpha']);
        $beta = Group::factory()->create(['event_id' => $groupPhase->id, 'name' => 'Beta']);
        Group::factory()->create(['event_id' => $groupPhase->id, 'name' => 'Empty']);
        $this->register($groupPhase, $inf1, true, true, true, ['group_id' => $alpha->id]);
        $this->register($groupPhase, $et, false, true, false, ['group_id' => $alpha->id]);
        $this->register($groupPhase, $inf2, true, true, true, ['group_id' => $beta->id]);

        // slot booking without requirements and alcohol, the requirements of a slot count for its row
        $slotBooking = $this->event('Slots', 'slot_booking', false, false);
        $early = Slot::factory()->create(['event_id' => $slotBooking->id, 'name' => 'Early', 'has_requirements' => true]);
        $late = Slot::factory()->create(['event_id' => $slotBooking->id, 'name' => 'Late', 'has_requirements' => false]);
        $this->register($slotBooking, $inf1, true, true, false, ['slot_id' => $early->id]);
        $this->register($slotBooking, $inf3, false, false, false, ['slot_id' => $early->id]);
        $this->register($slotBooking, $et, true, true, false, ['slot_id' => $late->id]);

        $rows = $this->parseCsv($this->downloadStatistics());

        $na = 'N/A';
        $this->assertSame([
            ['event_or_group', 'registered_users', 'present_users', 'not_present_users', 'present_percentage', 'fulfills_requirements', 'not_fulfills_requirements', 'fulfills_requirements_percentage', 'drinks_alcohol', 'drinks_no_alcohol', 'drinks_alcohol_percentage', ...Course::all()->map(fn (Course $course) => 'course_'.$course->abbreviation)->all()],
            // the admin has roles and is not counted
            $this->expectedRow('Global', ['4', $na, $na, $na, $na, $na, $na, $na, $na, $na], ['INF' => 3, 'ET' => 1]),
            $this->expectedRow('Rally', ['3', '2', '1', '66.67%', '2', '1', '66.67%', $na, $na, $na], ['INF' => 3]),
            $this->expectedRow('Group Phase', ['3', '2', '1', '66.67%', $na, $na, $na, '2', '1', '66.67%'], ['INF' => 2, 'ET' => 1]),
            $this->expectedRow('Group Phase - Alpha', ['2', '1', '1', '50%', $na, $na, $na, '1', '1', '50%'], ['INF' => 1, 'ET' => 1]),
            $this->expectedRow('Group Phase - Beta', ['1', '1', '0', '100%', $na, $na, $na, '1', '0', '100%'], ['INF' => 1]),
            $this->expectedRow('Group Phase - Empty', ['0', '0', '0', '0%', $na, $na, $na, '0', '0', '0%']),
            $this->expectedRow('Slots', ['3', '2', '1', '66.67%', $na, $na, $na, $na, $na, $na], ['INF' => 2, 'ET' => 1]),
            $this->expectedRow('Slots - Early', ['2', '1', '1', '50%', '1', '1', '50%', $na, $na, $na], ['INF' => 2]),
            $this->expectedRow('Slots - Late', ['1', '1', '0', '100%', $na, $na, $na, $na, $na, $na], ['ET' => 1]),
        ], $rows);
    }

    public function test_statistics_are_utf8_with_bom(): void
    {
        $this->event('Kneipentour – Gruppe Ü', 'event_registration', false, false);

        $csv = $this->downloadStatistics();

        $this->assertStringStartsWith("\xEF\xBB\xBF".'event_or_group;', $csv);
        $this->assertSame('Kneipentour – Gruppe Ü', $this->parseCsv($csv)[2][0]);
    }

    public function test_statistics_escape_names_that_would_run_as_formula(): void
    {
        $event = $this->event('=HYPERLINK("x")', 'group_phase', false, false);
        Group::factory()->create(['event_id' => $event->id, 'name' => 'Alpha']);
        $safeEvent = $this->event('Rally', 'slot_booking', false, false);
        Slot::factory()->create(['event_id' => $safeEvent->id, 'name' => '=1+1']);

        $rows = $this->parseCsv($this->downloadStatistics());

        $this->assertSame("'=HYPERLINK(\"x\")", $rows[2][0]);
        $this->assertSame("'=HYPERLINK(\"x\") - Alpha", $rows[3][0]);
        // only the start of a cell runs as formula, the event name comes first
        $this->assertSame('Rally - =1+1', $rows[5][0]);
    }

    public function test_user_without_permission_can_not_download_the_statistics(): void
    {
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->get(route('dashboard.admin.downloadStatistics'))
            ->assertRedirect();
    }
}
