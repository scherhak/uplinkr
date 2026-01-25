<?php

namespace Uplinkr\Console\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
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
                            {--scheduler : Enable automatic scheduler integration}';

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
    public function handle(): int
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

}
