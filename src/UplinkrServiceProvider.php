<?php

namespace Uplinkr;

use Illuminate\Support\ServiceProvider;
use Uplinkr\Console\Commands\Probe\ProbeUrlCommand;
use Uplinkr\Console\Commands\ProbeApiCommand;
use Uplinkr\Console\Commands\Project\ProjectAddProbeCommand;
use Uplinkr\Console\Commands\Project\ProjectAlertDecisionCommand;
use Uplinkr\Console\Commands\Project\ProjectAlertsCommand;
use Uplinkr\Console\Commands\Project\ProjectAnalyzeCommand;
use Uplinkr\Console\Commands\Project\ProjectArchiveCommand;
use Uplinkr\Console\Commands\Project\ProjectDisableCommand;
use Uplinkr\Console\Commands\Project\ProjectEnableCommand;
use Uplinkr\Console\Commands\Project\ProjectInitCommand;
use Uplinkr\Console\Commands\Project\ProjectListCommand;
use Uplinkr\Console\Commands\Project\ProjectRemoveProbeCommand;
use Uplinkr\Console\Commands\Project\ProjectRunProbesCommand;
use Uplinkr\Console\Commands\Project\ProjectRunSelectedProbeCommand;
use Uplinkr\Console\Commands\Project\ProjectUpdateCommand;
use Uplinkr\Console\Commands\Prune\PruneStorageCommand;
use Uplinkr\Handler\Probe\ResultHandler;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Handler\Project\ProbeAllProjectsHandler;
use Uplinkr\Handler\Project\ProbeSelectedProjectsHandler;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileProbeResultsStorage;
use Uplinkr\Storage\FileProjectStorage;
use Uplinkr\Support\Sanitizer;

use Illuminate\Support\Facades\Notification;
use Uplinkr\Handler\Project\Alerts\AlertNotificationHandler;
use Uplinkr\Notifications\Channels\UplinkrWebhookChannel;

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
                PruneStorageCommand::class,
                ProjectAnalyzeCommand::class,
                ProjectInitCommand::class,
                ProjectDisableCommand::class,
                ProjectEnableCommand::class,
                ProjectUpdateCommand::class,
                ProjectListCommand::class,
                ProjectArchiveCommand::class,
                ProjectAddProbeCommand::class,
                ProjectAlertDecisionCommand::class,
                ProjectAlertsCommand::class,
                ProjectRemoveProbeCommand::class,
                ProjectRunProbesCommand::class,
                ProjectRunSelectedProbeCommand::class,
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

        $this->app->singleton(ResultHandler::class, function ($app) {
            return new ResultHandler(
                $app->make(UplinkrConfig::class),
                $app->make(Sanitizer::class)
            );
        });

        $this->app->singleton(UrlHandler::class, function ($app) {
            return new UrlHandler(
                $app->make(ProbeResultsStorageInterface::class),
                $app->make(UplinkrConfig::class),
                $app->make(Sanitizer::class),
                $app->make(ResultHandler::class)
            );
        });

        $this->app->singleton(ProbeSelectedProjectsHandler::class, function ($app) {
            return new ProbeSelectedProjectsHandler(
                $app->make(ProjectStorageInterface::class),
                $app->make(UrlHandler::class)
            );
        });

        $this->app->singleton(ProbeAllProjectsHandler::class, function ($app) {
            return new ProbeAllProjectsHandler(
                $app->make(ProjectStorageInterface::class),
                $app->make(UrlHandler::class)
            );
        });

        $this->registerUplinkrLogChannel();
        $this->registerNotificationChannels();
    }

    /**
     * Registers custom notification channels.
     *
     * @return void
     */
    private function registerNotificationChannels(): void
    {
        Notification::extend('uplinkr-log', function ($app) {
            return new class {
                public function send($notifiable, $notification)
                {
                    if (method_exists($notification, 'toLog')) {
                        return $notification->toLog($notifiable);
                    }
                }
            };
        });

        Notification::extend('uplinkr-webhook', function ($app) {
            return $app->make(UplinkrWebhookChannel::class);
        });
    }

    /**
     * Registers the Uplinkr log channel in the application's logging configuration.
     *
     * This method checks if the Uplinkr log channel is already defined in the
     * application's logging channels. If the channel is already defined, the
     * method does not overwrite it. Otherwise, it defines the channel with the
     * configuration specified in `uplinkr.log`. If `uplinkr.log` is empty,
     * a default configuration is used to ensure the logging channel is functional.
     *
     * @return void
     */
    private function registerUplinkrLogChannel(): void
    {
        $channelName = config('uplinkr.log_channel', 'uplinkr');
        $channels = config('logging.channels', []);

        // If the host app has already defined the channel: respect it and DO NOT overwrite it.
        if (isset($channels[$channelName])) {
            return;
        }

        $definition = config('uplinkr.log', []);

        // Safeguard: if empty for any reason, set minimum default
        if (empty($definition)) {
            $definition = [
                'driver' => 'daily',
                'path' => storage_path('logs/uplinkr.log'),
                'level' => 'debug',
                'days' => 14,
                'replace_placeholders' => true,
            ];
        }

        // Channel zur Laufzeit in logging.channels einhängen
        config()?->set("logging.channels.{$channelName}", $definition);
    }
}
