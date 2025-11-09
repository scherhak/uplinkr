<?php

namespace Uplinkr\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Uplinkr\UplinkrServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            UplinkrServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Package-config Defaults, if it's necessary:
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.fallback_locale', 'en');
    }

    protected function defineTranslations($app): void
    {
        // if needed
    }
}
