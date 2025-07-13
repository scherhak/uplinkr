<?php

namespace Uplinkr;

use Illuminate\Support\ServiceProvider;
use Uplinkr\Commands\ProbeUri;

class UplinkrServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Config publishen
        $this->publishes([
            __DIR__.'/../config/uplinkr.php' => config_path('uplinkr.php'),
        ], 'uplinkr-config');

        // Migrations automatisch laden
//        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Commands registrieren (falls vorhanden)
        if ($this->app->runningInConsole()) {
            $this->commands([
                 ProbeUri::class,
            ]);
        }

        // Optional: Views laden, falls du ein Dashboard baust
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'uplinkr');

        // Optional: Routen laden
        // $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // Config mit Standardwerten mergen
        $this->mergeConfigFrom(
            __DIR__.'/../config/uplinkr.php', 'uplinkr'
        );
    }
}
