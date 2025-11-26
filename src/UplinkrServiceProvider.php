<?php

namespace Uplinkr;

use Illuminate\Support\ServiceProvider;
use Uplinkr\Commands\ProbeApi;
use Uplinkr\Commands\ProbeUrl;
use Uplinkr\Commands\StoragePrune;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileStorage;

/**
 * Class UplinkrServiceProvider
 * @package Uplinkr
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
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

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProbeUrl::class,
                ProbeApi::class,
                StoragePrune::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // merge config with standard config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/uplinkr.php', 'uplinkr'
        );

        $this->app->singleton(StorageInterface::class, function ($app) {
            $config = $app->make(UplinkrConfig::class);
            return new FileStorage($config);
        });

        $this->app->singleton(UplinkrConfig::class, function () {
            return UplinkrConfig::fromConfig();
        });
    }
}
