<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardAdminUserExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Course $electrical;

    private Course $computerScience;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();

        $this->electrical = Course::where('abbreviation', 'ET')->firstOrFail();
        $this->computerScience = Course::where('abbreviation', 'INF')->firstOrFail();

        $this->admin = $this->createUserWithRoles('admin', 'tutor');
        $this->admin->update(['firstname' => 'Ada', 'lastname' => 'Admin', 'course_id' => $this->computerScience->id]);
    }

    /**
     * Download the export as the given user and parse the csv into rows of cells.
     *
     * @return list<list<string>>
     */
    private function exportRows(?User $user = null): array
    {
        $csv = $this->actingAsTutor($user ?? $this->admin)
            ->get(route('dashboard.admin.exportUsers'))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=portals_users.csv')
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = explode("\n", trim(substr($csv, 3)));

        return array_map(fn (string $line) => str_getcsv($line, ';'), $lines);
    }

    /**
     * Create a user of the course with the given roles.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $roles
     */
    private function userOf(Course $course, array $attributes, array $roles = []): User
    {
        $user = User::factory()->create(['course_id' => $course->id, ...$attributes]);
        if ($roles) {
            $user->assignRole($roles);
        }

        return $user;
    }

    public function test_users_are_sorted_by_course_type_and_name(): void
    {
        Carbon::setTestNow('2026-11-13 14:05:00');
        $this->userOf($this->electrical, ['firstname' => 'Ben', 'lastname' => 'Zander', 'email' => 'ben@example.org']);
        $this->userOf($this->electrical, ['firstname' => 'Anna', 'lastname' => 'Becker', 'email' => 'anna@example.org', 'is_disabled' => true]);
        $this->userOf($this->electrical, ['firstname' => 'Tom', 'lastname' => 'Yilmaz', 'email' => 'tom@example.org'], ['tutor', 'stage tutor']);
        $this->userOf($this->electrical, ['firstname' => 'Prof', 'lastname' => 'Abel', 'email' => 'prof@example.org'], ['special']);
        $this->userOf($this->computerScience, ['firstname' => 'Cem', 'lastname' => 'Arslan', 'email' => 'cem@example.org']);

        $rows = $this->exportRows();

        $this->assertSame(['Kurs', 'Typ', 'Nachname', 'Vorname', 'E-Mail', 'Rollen', 'Deaktiviert', 'Angelegt am'], $rows[0]);
        $this->assertSame([
            ['B. Eng. Elektrotechnik', 'Tutor', 'Yilmaz', 'Tom', 'tom@example.org', 'stage tutor, tutor', 'Nein', '13.11.2026 14:05'],
            ['B. Eng. Elektrotechnik', 'Ersti', 'Becker', 'Anna', 'anna@example.org', '', 'Ja', '13.11.2026 14:05'],
            ['B. Eng. Elektrotechnik', 'Ersti', 'Zander', 'Ben', 'ben@example.org', '', 'Nein', '13.11.2026 14:05'],
            ['B. Eng. Elektrotechnik', 'Sonstige', 'Abel', 'Prof', 'prof@example.org', 'special', 'Nein', '13.11.2026 14:05'],
        ], array_slice($rows, 1, 4));
        // the courses are sorted by name, the admin is a tutor of computer science
        $this->assertSame(['B. Sc. Informatik', 'Tutor', 'Admin', 'Ada'], array_slice($rows[5], 0, 4));
        $this->assertSame(['B. Sc. Informatik', 'Ersti', 'Arslan', 'Cem'], array_slice($rows[6], 0, 4));
        $this->assertCount(7, $rows);
    }

    public function test_names_that_would_run_as_formula_are_escaped(): void
    {
        $this->userOf($this->electrical, ['firstname' => '=HYPERLINK("x")', 'lastname' => '-Minus']);

        $rows = $this->exportRows();

        $this->assertSame("'-Minus", $rows[1][2]);
        $this->assertSame('\'=HYPERLINK("x")', $rows[1][3]);
    }

    public function test_export_needs_the_permission_to_manage_users(): void
    {
        $this->actingAsTutor($this->createUserWithRoles('tutor'))
            ->get(route('dashboard.admin.exportUsers'))
            ->assertRedirect();

        $this->actingAs($this->createUserWithRoles())
            ->get(route('dashboard.admin.exportUsers'))
            ->assertRedirect();
    }
}
