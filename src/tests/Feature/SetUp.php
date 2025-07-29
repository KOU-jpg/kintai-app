<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class Setup extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DatabaseSeederを明示的に指定した方が安全です
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }
}
