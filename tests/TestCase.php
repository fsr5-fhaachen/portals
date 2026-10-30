<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * Inertia sends every page to the ssr server of a running vite dev server (public/hot). Tests must not
     * depend on it, an unreachable dev server delays every page by the connection timeout.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.ssr.enabled' => false]);
    }
}
