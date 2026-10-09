<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $defaultConnection = config('database.default');
        $dbName = config("database.connections.{$defaultConnection}.database");

        if ($dbName === 'hoyos') {
            throw new \RuntimeException('FATAL SAFETY VIOLATION: Tests are strictly forbidden from running against the live "hoyos" database!');
        }
    }
}
