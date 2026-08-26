<?php

namespace Zakafk\FilamentTranslatableSelect\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Zakafk\FilamentTranslatableSelect\FilamentTranslatableSelectServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentTranslatableSelectServiceProvider::class,
        ];
    }
}
