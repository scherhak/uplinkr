<?php

namespace Uplinkr\Tests\Console\Commands\Install;

use File;
use PHPUnit\Framework\Attributes\Test;
use Uplinkr\Tests\TestCase;
use Uplinkr\Support\CliIcon;

class UplinkrInstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure config_path('uplinkr.php') exists for some tests or is clean
        if (File::exists(config_path('uplinkr.php'))) {
            File::delete(config_path('uplinkr.php'));
        }
    }

    #[Test]
    public function it_publishes_assets_and_shows_success_messages(): void
    {
        $this->artisan('uplinkr:install')
            ->expectsOutputToContain(__('uplinkr::messages.install_running'))
            ->expectsOutputToContain(__('uplinkr::messages.install_assets_published'))
            ->expectsOutputToContain(__('uplinkr::messages.install_config_hint'))
            ->expectsOutputToContain(__('uplinkr::messages.install_scheduler_not_enabled'))
            ->expectsOutputToContain(__('uplinkr::messages.install_complete'))
            ->assertExitCode(0);
    }

    #[Test]
    public function it_enables_the_scheduler_when_option_is_provided(): void
    {
        // Setup: Create a dummy config file with enabled => false
        $configPath = config_path('uplinkr.php');
        if (!File::isDirectory(dirname($configPath))) {
            File::makeDirectory(dirname($configPath), 0755, true);
        }
        File::put($configPath, "<?php return ['scheduler' => ['enabled' => false]];");

        $this->artisan('uplinkr:install --scheduler')
            ->expectsOutputToContain(__('uplinkr::messages.install_scheduler_enabled'))
            ->assertExitCode(0);

        $this->assertStringContainsString("'enabled' => true", File::get($configPath));
    }

    #[Test]
    public function it_warns_if_scheduler_is_already_enabled(): void
    {
        // Setup: Create a dummy config file with enabled => true
        $configPath = config_path('uplinkr.php');
        if (!File::isDirectory(dirname($configPath))) {
            File::makeDirectory(dirname($configPath), 0755, true);
        }
        File::put($configPath, "<?php return ['scheduler' => ['enabled' => true]];");

        $this->artisan('uplinkr:install --scheduler')
            ->expectsOutputToContain(__('uplinkr::messages.install_scheduler_already_enabled'))
            ->assertExitCode(0);
    }

}
