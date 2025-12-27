<?php

namespace Uplinkr;

use Illuminate\Support\ServiceProvider;
use Uplinkr\Console\Commands\AnalyzeProjectCommand;
use Uplinkr\Console\Commands\ProbeApiCommand;
use Uplinkr\Console\Commands\ProbeUrlCommand;
use Uplinkr\Console\Commands\Project\ProjectAddProbeCommand;
use Uplinkr\Console\Commands\Project\ProjectArchiveCommand;
use Uplinkr\Console\Commands\Project\ProjectInitCommand;
use Uplinkr\Console\Commands\Project\ProjectListCommand;
use Uplinkr\Console\Commands\StoragePruneCommand;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileProbeResultsStorage;
use Uplinkr\Storage\FileProjectStorage;
use Uplinkr\Support\Sanitizer;

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
                ProbeUrlCommand::class,
                StoragePruneCommand::class,
                AnalyzeProjectCommand::class,
                ProjectInitCommand::class,
                ProjectListCommand::class,
                ProjectArchiveCommand::class,
                ProjectAddProbeCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/uplinkr.php', 'uplinkr');

        $this->app->singleton(UplinkrConfig::class, function () {
            return UplinkrConfig::fromConfig();
        });

        $this->app->singleton(Sanitizer::class, function ($app) {
            return new Sanitizer(
                config: $app->make(UplinkrConfig::class)
            );
        });

        $this->app->singleton(ProbeResultsStorageInterface::class, function ($app) {
            $config = $app->make(UplinkrConfig::class);
            $sanitizer = $app->make(Sanitizer::class);

            return new FileProbeResultsStorage($config, $sanitizer);
        });

        $this->app->singleton(ProjectStorageInterface::class, function ($app) {
            $config = $app->make(UplinkrConfig::class);
            $sanitizer = $app->make(Sanitizer::class);

            return new FileProjectStorage($config, $sanitizer);
        });
    }
}
