<?php

namespace Uplinkr\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Uplinkr\UplinkrServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            UplinkrServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Package-config Defaults, if it's necessary:
        // $app['config']->set('uplinkr.timeout', 10);
        $app['config']->set('app.locale', 'en');     // oder 'de'
        $app['config']->set('app.fallback_locale', 'en');
    }

    protected function defineTranslations($app): void
    {
        // if needed
    }
}
