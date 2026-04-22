<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Suppress Vite manifest errors in tests — assets are not compiled during test runs
        $this->withoutVite();
    }
}
