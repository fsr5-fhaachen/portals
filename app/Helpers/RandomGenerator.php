<?php

namespace App\Helpers;

use App\Models\Course;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Settings, pool and draw of the random generator. The settings are stored as json in the state "randomGeneratorSettings".
 * Either the first-year students (users without a role) or the tutors take part. The pool contains the participants
 * of the selected courses that did not opt out and were not drawn yet.
 */
class RandomGenerator
{
    /**
     * Key of the state with the settings.
     */
    public const SETTINGS_KEY = 'randomGeneratorSettings';

    /**
     * The first-year students (users without a role) take part.
     */
    public const PARTICIPANTS_FIRST_YEARS = 'first_years';

    /**
     * The tutors (and stage tutors) take part.
     */
    public const PARTICIPANTS_TUTORS = 'tutors';

    /**
     * Roles of the users that take part as tutors.
     *
     * @var list<string>
     */
    public const TUTOR_ROLES = ['tutor', 'stage tutor'];

    /**
     * Get the settings. Without stored settings all courses are selected.
     *
     * @return array{participants: string, course_ids: list<int>, opt_out_enabled: bool, tutor_opt_out_enabled: bool, hide_opted_out: bool, hide_other_courses: bool, hide_drawn: bool}
     */
    public function settings(): array
    {
        $stored = json_decode(State::where('key', self::SETTINGS_KEY)->value('value') ?? '{}', true);

        return [
            'participants' => ($stored['participants'] ?? null) === self::PARTICIPANTS_TUTORS ? self::PARTICIPANTS_TUTORS : self::PARTICIPANTS_FIRST_YEARS,
            'course_ids' => array_map('intval', $stored['course_ids'] ?? Course::pluck('id')->all()),
            'opt_out_enabled' => (bool) ($stored['opt_out_enabled'] ?? false),
            'tutor_opt_out_enabled' => (bool) ($stored['tutor_opt_out_enabled'] ?? false),
            'hide_opted_out' => (bool) ($stored['hide_opted_out'] ?? false),
            'hide_other_courses' => (bool) ($stored['hide_other_courses'] ?? false),
            'hide_drawn' => (bool) ($stored['hide_drawn'] ?? false),
        ];
    }

    /**
     * Store the settings.
     *
     * @param  array{participants: string, course_ids: list<int>, opt_out_enabled: bool, tutor_opt_out_enabled: bool, hide_opted_out: bool, hide_other_courses: bool, hide_drawn: bool}  $settings
     */
    public function saveSettings(array $settings): void
    {
        State::updateOrCreate(['key' => self::SETTINGS_KEY], ['value' => json_encode($settings)]);
    }

    /**
     * Check if the first-year students can opt out of the random generator on their own page.
     */
    public function isOptOutEnabled(): bool
    {
        return $this->settings()['opt_out_enabled'];
    }

    /**
     * Check if the tutors can opt out of the random generator on their own page.
     */
    public function isTutorOptOutEnabled(): bool
    {
        return $this->settings()['tutor_opt_out_enabled'];
    }

    /**
     * Check if the tutors take part instead of the first-year students.
     */
    public function drawsTutors(): bool
    {
        return $this->settings()['participants'] === self::PARTICIPANTS_TUTORS;
    }

    /**
     * Get the query of all participants of the selected group (first-year students or tutors).
     *
     * @return Builder<User>
     */
    public function participants(): Builder
    {
        return $this->drawsTutors() ? $this->tutors() : User::doesntHave('roles');
    }

    /**
     * Get the query of the users of both groups (first-year students and tutors), e.g. for the display that is not
     * reloaded when the group changes.
     *
     * @return Builder<User>
     */
    public function candidates(): Builder
    {
        return User::where(fn (Builder $query) => $query
            ->doesntHave('roles')
            ->orWhereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', self::TUTOR_ROLES)));
    }

    /**
     * Get the query of the participants that can be drawn with the current settings.
     *
     * @return Builder<User>
     */
    public function pool(): Builder
    {
        return $this->participants()
            ->whereIn('course_id', $this->settings()['course_ids'])
            ->where('random_generator_opt_out', false)
            ->whereNull('random_generator_drawn_at');
    }

    /**
     * Get the query of the tutors (users with the role tutor or stage tutor).
     *
     * @return Builder<User>
     */
    private function tutors(): Builder
    {
        return User::whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', self::TUTOR_ROLES));
    }

    /**
     * Draw a random user of the pool and mark the user as drawn. A preselected user overrides the pool and is drawn
     * in any case. Returns null if the pool is empty.
     */
    public function draw(?User $preselectedUser = null): ?User
    {
        $user = $preselectedUser ?? $this->pool()->inRandomOrder()->first();
        if (! $user) {
            return null;
        }

        $user->random_generator_drawn_at = now();
        $user->save();

        return $user;
    }

    /**
     * Reset the drawn state of all users, so that they can be drawn again.
     */
    public function resetDrawn(): void
    {
        User::whereNotNull('random_generator_drawn_at')->update(['random_generator_drawn_at' => null]);
    }
}
