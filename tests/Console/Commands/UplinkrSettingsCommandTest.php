<?php

namespace Uplinkr\Tests\Console\Commands;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Support\CliIcon;
use Uplinkr\Tests\TestCase;

class UplinkrSettingsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_it_updates_iam_alive_settings(): void
    {
        $this->artisan('uplinkr:settings', [
            '--iam-alive-enabled' => 'true',
            '--iam-alive-interval-hours' => '8',
            '--iam-alive-channels' => 'mail,log',
            '--force' => true,
        ])
            ->expectsOutput(CliIcon::OK->label(__('uplinkr::messages.settings_iam_alive_updated')))
            ->assertExitCode(0);

        Storage::disk('local')->assertExists('uplinkr/settings.json');
        $saved = json_decode((string)Storage::disk('local')->get('uplinkr/settings.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(true, $saved['iam_alive']['enabled']);
        $this->assertSame(8, $saved['iam_alive']['interval_hours']);
        $this->assertSame(['mail', 'log'], $saved['iam_alive']['channels']);
        $this->assertNull($saved['iam_alive']['last_sent_at']);
    }

    public function test_it_validates_iam_alive_interval_hours_range(): void
    {
        $this->artisan('uplinkr:settings', [
            '--iam-alive-enabled' => 'true',
            '--iam-alive-interval-hours' => '0',
            '--force' => true,
        ])
            ->expectsOutput(CliIcon::ERROR->label(__('uplinkr::messages.settings_validation_failed')))
            ->assertExitCode(2);
    }
}
