<?php

namespace Uplinkr;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use JsonException;
use Uplinkr\Console\Commands\Probe\ProbeUrlCommand;
use Uplinkr\Console\Commands\Project\ProjectAddProbeCommand;
use Uplinkr\Console\Commands\Project\ProjectAlertDecisionCommand;
use Uplinkr\Console\Commands\Project\ProjectAlertsCommand;
use Uplinkr\Console\Commands\Project\ProjectAnalyzeCommand;
use Uplinkr\Console\Commands\Project\ProjectArchiveCommand;
use Uplinkr\Console\Commands\Project\ProjectInitCommand;
use Uplinkr\Console\Commands\Project\ProjectListCommand;
use Uplinkr\Console\Commands\Project\ProjectRemoveProbeCommand;
use Uplinkr\Console\Commands\Project\ProjectRunProbesCommand;
use Uplinkr\Console\Commands\Project\ProjectRunSelectedCommand;
use Uplinkr\Console\Commands\Project\ProjectUpdateCommand;
use Uplinkr\Console\Commands\Prune\PruneStorageCommand;
use Uplinkr\Console\Commands\UplinkrConfigCommand;
use Uplinkr\Console\Commands\UplinkrIamAliveCommand;
use Uplinkr\Console\Commands\UplinkrInstallCommand;
use Uplinkr\Console\Commands\UplinkrSettingsCommand;
use Uplinkr\Handler\Probe\ResultHandler;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Handler\Project\Probes\ProbeAllProjectsHandler;
use Uplinkr\Handler\Project\Probes\ProbeSelectedProjectsHandler;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Notifications\Channels\UplinkrWebhookChannel;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileProbeResultsStorage;
use Uplinkr\Storage\FileProjectStorage;
use Uplinkr\Storage\FileSettingsStorage;
use Uplinkr\Support\Logger;
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

        // Register console commands and scheduler
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProbeUrlCommand::class,
                PruneStorageCommand::class,
                ProjectAnalyzeCommand::class,
                ProjectInitCommand::class,
                ProjectUpdateCommand::class,
                ProjectListCommand::class,
                ProjectArchiveCommand::class,
                ProjectAddProbeCommand::class,
                ProjectAlertDecisionCommand::class,
                ProjectAlertsCommand::class,
                ProjectRemoveProbeCommand::class,
                ProjectRunProbesCommand::class,
                ProjectRunSelectedCommand::class,
                UplinkrInstallCommand::class,
                UplinkrConfigCommand::class,
                UplinkrIamAliveCommand::class,
                UplinkrSettingsCommand::class,
            ]);

            $this->app->booted(function () {
                if (!config('uplinkr.scheduler.enabled')) {
                    return;
                }

                /** @var Schedule $schedule */
                $schedule = app(Schedule::class);
                $probeCron = (string)config('uplinkr.scheduler.cron');
                $alertCron = config('uplinkr.scheduler.alert_cron');
                $alertCron = is_string($alertCron) && $alertCron !== '' ? $alertCron : $probeCron;

                $schedule->command('uplinkr:project:run-probes --force')
                    ->cron($probeCron)
                    ->withoutOverlapping()
                    ->runInBackground();

                $schedule->command('uplinkr:project:alert:decision')
                    ->cron($alertCron)
                    ->withoutOverlapping();

                try {
                    $settingsStorage = app(FileSettingsStorage::class);
                    $iamAlive = $settingsStorage->getIamAliveSettings();
                    $enabled = (bool)($iamAlive['enabled'] ?? false);
                    $intervalHours = (int)($iamAlive['interval_hours'] ?? 24);

                    if ($enabled && $intervalHours >= 1 && $intervalHours <= 24) {
                        $schedule->command('uplinkr:iam-alive --scheduled')
                            ->everyMinute()
                            ->withoutOverlapping();
                    }
                } catch (JsonException $exception) {
                    Logger::log()->warning('Unable to load uplinkr settings.json for scheduler.', [
                        'reason' => $exception->getMessage(),
                    ]);
                }
            });
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

            return new FileProbeResultsStorage(config: $config, sanitizer: $sanitizer);
        });

        $this->app->singleton(ProjectStorageInterface::class, function ($app) {
            $config = $app->make(UplinkrConfig::class);
            $sanitizer = $app->make(Sanitizer::class);

            return new FileProjectStorage(config: $config, sanitizer: $sanitizer);
        });

        $this->app->singleton(ResultHandler::class, function ($app) {
            return new ResultHandler(
                config: $app->make(UplinkrConfig::class),
                sanitizer: $app->make(Sanitizer::class)
            );
        });

        $this->app->singleton(UrlHandler::class, function ($app) {
            return new UrlHandler(
                storage: $app->make(ProbeResultsStorageInterface::class),
                config: $app->make(UplinkrConfig::class),
                sanitizer: $app->make(Sanitizer::class),
                resultHandler: $app->make(ResultHandler::class)
            );
        });

        $this->app->singleton(ProbeSelectedProjectsHandler::class, function ($app) {
            return new ProbeSelectedProjectsHandler(
                projectStorage: $app->make(ProjectStorageInterface::class),
                urlHandler: $app->make(UrlHandler::class)
            );
        });

        $this->app->singleton(ProbeAllProjectsHandler::class, function ($app) {
            return new ProbeAllProjectsHandler(
                projectStorage: $app->make(ProjectStorageInterface::class),
                urlHandler: $app->make(UrlHandler::class)
            );
        });

        $this->app->singleton(FileSettingsStorage::class, function ($app) {
            return new FileSettingsStorage(
                config: $app->make(UplinkrConfig::class)
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
        $config = UplinkrConfig::fromConfig();
        $channelName = $config->getLogChannel();
        $channels = config('logging.channels', []);

        // If the host app has already defined the channel: respect it and DO NOT overwrite it.
        if (isset($channels[$channelName])) {
            return;
        }

        $definition = $config->getLogDefinition();

        // Safeguard: if empty for any reason, set a minimum default
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
