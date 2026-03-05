<?php

namespace Uplinkr\Console\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Storage\FileSettingsStorage;
use Uplinkr\Support\CliIcon;
use Uplinkr\Traits\HandlesProbeOutput;
use Uplinkr\UplinkrServiceProvider;

/**
 * Class ProbeUrlCommand
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-url` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class UplinkrInstallCommand extends Command
{
    use HandlesProbeOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:install 
                            {--scheduler : Enable automatic scheduler integration}
                            {--iam-alive : Enable I\'m alive notifications}
                            {--iam-alive-interval-hours= : Set I\'m alive interval in hours (1-24)}
                            {--iam-alive-channels= : Comma separated I\'m alive channels (mail,log,webhook)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Uplinkr (publish config & language files, optional scheduler setup).';

    /**
     * Handles the installation process for Uplinkr.
     *
     * This method performs the necessary steps to install Uplinkr. It publishes the required
     * configuration and language files, optionally enables scheduler integration based on user input,
     * and provides feedback to the user about the installation progress and status.
     *
     * @return int Returns the status code indicating the success or failure of the operation.
     * @throws FileNotFoundException
     */
    public function handle(FileSettingsStorage $settingsStorage): int
    {
        $this->info(CliIcon::RUN->label(text: __('uplinkr::messages.install_running')));

        // 1. Publish config + lang
        $this->publishAssets();

        // 2. Optional scheduler enable
        if ($this->option('scheduler')) {
            $this->enableScheduler();
        } else {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.install_scheduler_not_enabled')));
            $this->line(__('uplinkr::messages.install_scheduler_enable_later'));
        }

        if (!$this->configureIamAliveSettings($settingsStorage)) {
            return CommandAlias::INVALID;
        }

        $this->newLine();
        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.install_complete')));

        return self::SUCCESS;
    }

    /**
     * Publishes the configuration and language assets for the application.
     *
     * This method invokes a silent vendor publish command to copy the necessary configuration
     * and translation files from the specified provider to the application's corresponding directories.
     *
     * @return void
     */
    private function publishAssets(): void
    {
        $this->callSilent('vendor:publish', [
            '--provider' => UplinkrServiceProvider::class,
            '--tag' => ['uplinkr-config', 'uplinkr-lang'],
        ]);

        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.install_assets_published')));
        $this->info(CliIcon::INFO->label(text: __('uplinkr::messages.install_config_hint')));
    }

    /**
     * Enables the integration of the scheduler by updating the configuration file.
     *
     * This method checks for the existence of the configuration file and modifies its content
     * to enable the scheduler if it is not already enabled. If the configuration file is missing
     * or the scheduler is already enabled, appropriate messages are displayed.
     *
     * @return void
     * @throws FileNotFoundException
     */
    private function enableScheduler(): void
    {
        $configPath = config_path('uplinkr.php');

        if (!File::exists($configPath)) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.install_config_not_found')));
            return;
        }

        $content = File::get($configPath);

        if (str_contains($content, "'enabled' => true")) {
            $this->line(CliIcon::WARN->label(text: __('uplinkr::messages.install_scheduler_already_enabled')));
            return;
        }

        $content = str_replace(
            "'enabled' => false",
            "'enabled' => true",
            $content
        );

        File::put($configPath, $content);

        $this->line(CliIcon::OK->label(text: __('uplinkr::messages.install_scheduler_enabled')));
    }

    /**
     * Configures "I'm alive" settings via settings.json.
     *
     * @param FileSettingsStorage $settingsStorage
     * @return bool
     * @throws FileNotFoundException
     * @throws \JsonException
     */
    private function configureIamAliveSettings(FileSettingsStorage $settingsStorage): bool
    {
        $enabledOption = $this->option('iam-alive') ? 'true' : null;
        $intervalOption = $this->option('iam-alive-interval-hours');
        $channelsOption = $this->option('iam-alive-channels');

        if ($enabledOption === null && $intervalOption === null && $channelsOption === null) {
            return true;
        }

        $enabled = filter_var($enabledOption, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $interval = $intervalOption !== null ? (int)$intervalOption : null;
        $channels = $channelsOption !== null ? $this->parseChannels((string)$channelsOption) : null;

        $validator = Validator::make(
            [
                'enabled' => $enabled,
                'interval_hours' => $interval,
                'channels' => $channels,
            ],
            [
                'enabled' => 'nullable|boolean',
                'interval_hours' => 'nullable|integer|min:1|max:24',
                'channels' => 'nullable|array|min:1',
                'channels.*' => 'in:mail,log,webhook',
            ]
        );

        if ($validator->fails()) {
            $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.install_iam_alive_invalid_settings')));
            return false;
        }

        if ($enabled === null && ($interval !== null || $channels !== null)) {
            $enabled = true;
        }

        $updated = $settingsStorage->updateIamAliveSettings($enabled, $interval, $channels);

        $this->line(CliIcon::OK->label(text: __('uplinkr::messages.install_iam_alive_configured', [
            'enabled' => $updated['enabled'] ? 'true' : 'false',
            'interval' => $updated['interval_hours'],
            'channels' => implode(',', (array)$updated['channels']),
        ])));

        if (!$this->option('scheduler')) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.install_iam_alive_scheduler_hint')));
        }

        return true;
    }

    /**
     * @param string $channels
     * @return array
     */
    private function parseChannels(string $channels): array
    {
        $parts = explode(',', strtolower($channels));
        $parts = array_map(static fn(string $channel): string => trim($channel), $parts);
        $parts = array_values(array_filter($parts, static fn(string $channel): bool => $channel !== ''));

        return array_values(array_unique($parts));
    }

}
