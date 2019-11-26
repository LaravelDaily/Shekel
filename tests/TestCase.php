<?php

namespace Shekel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Shekel\ShekelServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [ShekelServiceProvider::class];
    }
}