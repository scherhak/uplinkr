<?php

namespace Uplinkr\Tests\Console\Commands;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Uplinkr\Notifications\IamAliveNotification;
use Uplinkr\Support\CliIcon;
use Uplinkr\Tests\TestCase;

class UplinkrIamAliveCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_it_sends_an_iam_alive_mail_notification(): void
    {
        Notification::fake();

        config()->set('uplinkr.notifications.channels.mail.enabled', true);
        config()->set('uplinkr.notifications.channels.mail.to', ['ops@example.com']);
        Storage::disk('local')->put('uplinkr/settings.json', json_encode([
            'iam_alive' => [
                'enabled' => true,
                'interval_hours' => 2,
                'channels' => ['mail'],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('uplinkr:iam-alive')
            ->expectsOutput(CliIcon::OK->label(__('uplinkr::messages.iam_alive_sent', ['channels' => 'mail'])))
            ->assertExitCode(0);

        Notification::assertSentOnDemand(IamAliveNotification::class);
    }

    public function test_it_skips_when_no_mail_recipients_are_configured(): void
    {
        Notification::fake();

        config()->set('uplinkr.notifications.channels.mail.enabled', true);
        config()->set('uplinkr.notifications.channels.mail.to', []);
        Storage::disk('local')->put('uplinkr/settings.json', json_encode([
            'iam_alive' => [
                'enabled' => true,
                'interval_hours' => 2,
                'channels' => ['mail'],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('uplinkr:iam-alive')
            ->expectsOutput(CliIcon::WARN->label(__('uplinkr::messages.iam_alive_no_recipients')))
            ->expectsOutput(CliIcon::WARN->label(__('uplinkr::messages.iam_alive_no_channels')))
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_it_skips_when_iam_alive_is_disabled(): void
    {
        Notification::fake();

        Storage::disk('local')->put('uplinkr/settings.json', json_encode([
            'iam_alive' => [
                'enabled' => false,
                'interval_hours' => 2,
                'channels' => ['mail'],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('uplinkr:iam-alive')
            ->expectsOutput(CliIcon::WARN->label(__('uplinkr::messages.iam_alive_disabled')))
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
