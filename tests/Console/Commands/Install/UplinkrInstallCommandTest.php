<?php

namespace Uplinkr\Tests\Console\Commands\Install;

use File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Uplinkr\Tests\TestCase;
use Uplinkr\Support\CliIcon;

class UplinkrInstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        
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

    #[Test]
    public function it_configures_iam_alive_settings_when_options_are_provided(): void
    {
        $this->artisan('uplinkr:install --iam-alive --iam-alive-interval-hours=6 --iam-alive-channels=mail,webhook')
            ->expectsOutputToContain('I\'m alive configured:')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists('uplinkr/settings.json');

        $saved = json_decode((string)Storage::disk('local')->get('uplinkr/settings.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(true, $saved['iam_alive']['enabled']);
        $this->assertSame(6, $saved['iam_alive']['interval_hours']);
        $this->assertSame(['mail', 'webhook'], $saved['iam_alive']['channels']);
    }

    #[Test]
    public function it_fails_for_invalid_iam_alive_interval_hours(): void
    {
        $this->artisan('uplinkr:install --iam-alive --iam-alive-interval-hours=25')
            ->expectsOutputToContain(__('uplinkr::messages.install_iam_alive_invalid_settings'))
            ->assertExitCode(2);
    }

}
