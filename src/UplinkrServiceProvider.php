<?php

namespace Uplinkr;

use Illuminate\Support\ServiceProvider;
use Uplinkr\Commands\ProbeUri;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Storage\DatabaseStorage;
use Uplinkr\Storage\FileStorage;

/**
 * Class UplinkrServiceProvider
 * @package Uplinkr
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 */
class UplinkrServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // publish config
        $this->publishes([
            __DIR__ . '/../config/uplinkr.php' => config_path('uplinkr.php'),
        ], 'uplinkr-config');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'uplinkr');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/uplinkr'),
        ], 'uplinkr-lang');

        // Upcoming migrations
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProbeUri::class,
            ]);
        }

        // Upcoming Dashboard
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'uplinkr');

        // Upcoming Routes
        // $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // Config mit Standardwerten mergen
        $this->mergeConfigFrom(
            __DIR__ . '/../config/uplinkr.php', 'uplinkr'
        );

        $this->app->singleton(StorageInterface::class, function ($app) {
            return config('uplinkr.storage.driver') === 'file'
                ? new FileStorage()
                : new DatabaseStorage();
        });
    }
}
