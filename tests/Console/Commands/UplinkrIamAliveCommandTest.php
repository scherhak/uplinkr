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

        Storage::disk('local')->put('uplinkr/project-a/settings.json', json_encode([
            'project' => 'project-a',
            'status' => 'enabled',
            'probes' => [
                ['url' => 'https://example.com'],
                ['url' => 'https://example.com/fail'],
            ],
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->put('uplinkr/project-a/state.json', json_encode([
            'project' => 'project-a',
            'probes' => [
                'GET https://example.com' => ['consecutive_failures' => 0],
                'GET https://example.com/fail' => ['consecutive_failures' => 3],
            ],
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->put('uplinkr/project-b/settings.json', json_encode([
            'project' => 'project-b',
            'status' => 'disabled',
            'probes' => [
                ['url' => 'https://api.example.com'],
            ],
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->put('uplinkr/project-b/state.json', json_encode([
            'project' => 'project-b',
            'probes' => [
                'GET https://api.example.com' => ['consecutive_failures' => 0],
            ],
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->put('uplinkr/settings.json', json_encode([
            'iam_alive' => [
                'enabled' => true,
                'interval_hours' => 2,
                'channels' => ['mail'],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('uplinkr:iam-alive')
            ->expectsOutputToContain(__('uplinkr::messages.iam_alive_sent', ['channels' => 'mail']))
            ->assertExitCode(0);

        Notification::assertSentOnDemand(IamAliveNotification::class, function ($notification, $channels, $notifiable): bool {
            $payload = $notification->toArray($notifiable);
            return ($payload['summary']['active_projects'] ?? null) === 1
                && ($payload['summary']['configured_probes'] ?? null) === 3
                && ($payload['summary']['successful_checks'] ?? null) === 2
                && ($payload['summary']['failed_checks'] ?? null) === 1
                && is_string($payload['settings']['iam_alive']['last_sent_at'] ?? null)
                && ($payload['settings']['iam_alive']['channels'] ?? []) === ['mail'];
        });

        $savedSettings = json_decode((string)Storage::disk('local')->get('uplinkr/settings.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsString($savedSettings['iam_alive']['last_sent_at'] ?? null);
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
