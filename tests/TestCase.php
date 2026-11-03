<?php

namespace Tests;

use App\Models\Module;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment.
     *
     * Inertia sends every page to the ssr server of a running vite dev server (public/hot). Tests must not
     * depend on it, an unreachable dev server delays every page by the connection timeout.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
    }

    /**
     * Seed roles, permissions, modules and courses.
     */
    protected function seedBasics(): void
    {
        $this->seed([
            RoleSeeder::class,
            ModuleSeeder::class,
            CourseSeeder::class,
        ]);
    }

    /**
     * Activate the module with the given key.
     */
    protected function activateModule(string $key): void
    {
        Module::where('key', $key)->update(['active' => true]);
    }

    /**
     * Create a user with the given roles.
     */
    protected function createUserWithRoles(string ...$roles): User
    {
        $user = User::factory()->create();

        if ($roles) {
            $user->assignRole($roles);
        }

        return $user;
    }

    /**
     * Act as the given user with an active tutor session.
     */
    protected function actingAsTutor(User $user): static
    {
        return $this->actingAs($user)->withSession(['tutor' => true]);
    }

    /**
     * Enable model auditing, which is disabled for console runs (and therefore PHPUnit) by default.
     */
    protected function enableModelAuditing(): void
    {
        config(['audit.console' => true]);
    }
}
