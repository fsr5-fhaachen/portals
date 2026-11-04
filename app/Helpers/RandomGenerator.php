<?php

namespace App\Helpers;

use App\Models\Course;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Settings, pool and draw of the random generator. The settings are stored as json in the state "randomGeneratorSettings".
 * Only users without a role (no tutors and admins) take part. The pool contains the participants of the selected
 * courses that did not opt out and were not drawn yet.
 */
class RandomGenerator
{
    /**
     * Key of the state with the settings.
     */
    public const SETTINGS_KEY = 'randomGeneratorSettings';

    /**
     * Get the settings. Without stored settings all courses are selected.
     *
     * @return array{course_ids: list<int>, opt_out_enabled: bool, hide_opted_out: bool, hide_other_courses: bool, hide_drawn: bool}
     */
    public function settings(): array
    {
        $stored = json_decode(State::where('key', self::SETTINGS_KEY)->value('value') ?? '{}', true);

        return [
            'course_ids' => array_map('intval', $stored['course_ids'] ?? Course::pluck('id')->all()),
            'opt_out_enabled' => (bool) ($stored['opt_out_enabled'] ?? false),
            'hide_opted_out' => (bool) ($stored['hide_opted_out'] ?? false),
            'hide_other_courses' => (bool) ($stored['hide_other_courses'] ?? false),
            'hide_drawn' => (bool) ($stored['hide_drawn'] ?? false),
        ];
    }

    /**
     * Store the settings.
     *
     * @param  array{course_ids: list<int>, opt_out_enabled: bool, hide_opted_out: bool, hide_other_courses: bool, hide_drawn: bool}  $settings
     */
    public function saveSettings(array $settings): void
    {
        State::updateOrCreate(['key' => self::SETTINGS_KEY], ['value' => json_encode($settings)]);
    }

    /**
     * Check if the users can opt out of the random generator on their own page.
     */
    public function isOptOutEnabled(): bool
    {
        return $this->settings()['opt_out_enabled'];
    }

    /**
     * Get the query of all participants (users without a role).
     *
     * @return Builder<User>
     */
    public function participants(): Builder
    {
        return User::doesntHave('roles');
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
