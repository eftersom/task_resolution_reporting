<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        putenv('DB_CONNECTION=mysql_testing');
        $_ENV['DB_CONNECTION'] = 'mysql_testing';
        $_SERVER['DB_CONNECTION'] = 'mysql_testing';

        parent::setUp();
    }
}
