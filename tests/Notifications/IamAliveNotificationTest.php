<?php

namespace Uplinkr\Tests\Notifications;

use Uplinkr\Notifications\IamAliveNotification;
use Uplinkr\Tests\TestCase;

class IamAliveNotificationTest extends TestCase
{
    public function test_to_array_contains_summary_and_settings(): void
    {
        $notification = new IamAliveNotification(
            channels: ['mail', 'log'],
            payload: [
                'summary' => [
                    'active_projects' => 2,
                    'configured_probes' => 8,
                    'successful_checks' => 6,
                    'failed_checks' => 2,
                ],
                'settings' => [
                    'iam_alive' => [
                        'enabled' => true,
                        'interval_hours' => 4,
                        'channels' => ['mail', 'log'],
                        'last_sent_at' => '2026-03-05 09:00:00',
                    ],
                ],
                'sent_at' => '2026-03-05 10:00:00',
            ]
        );

        $payload = $notification->toArray(null);

        $this->assertSame(2, $payload['summary']['active_projects']);
        $this->assertSame(8, $payload['summary']['configured_probes']);
        $this->assertSame(6, $payload['summary']['successful_checks']);
        $this->assertSame(2, $payload['summary']['failed_checks']);
        $this->assertSame('2026-03-05 09:00:00', $payload['settings']['iam_alive']['last_sent_at']);
        $this->assertSame('2026-03-05 10:00:00', $payload['timestamp']);
    }

    public function test_to_mail_contains_summary_and_settings_lines(): void
    {
        $notification = new IamAliveNotification(
            channels: ['mail'],
            payload: [
                'summary' => [
                    'active_projects' => 1,
                    'configured_probes' => 3,
                    'successful_checks' => 2,
                    'failed_checks' => 1,
                ],
                'settings' => [
                    'iam_alive' => [
                        'enabled' => true,
                        'interval_hours' => 2,
                        'channels' => ['mail'],
                        'last_sent_at' => '2026-03-05 11:00:00',
                    ],
                ],
            ]
        );

        $mail = $notification->toMail(null);

        $this->assertContains('- Active projects: 1', $mail->introLines);
        $this->assertContains('- Configured probes/checks: 3', $mail->introLines);
        $this->assertContains('- Successful checks (current state): 2', $mail->introLines);
        $this->assertContains('- Failed checks (current state): 1', $mail->introLines);
        $this->assertContains('- Enabled: true', $mail->introLines);
        $this->assertContains('- Interval (hours): 2', $mail->introLines);
        $this->assertContains('- Channels: mail', $mail->introLines);
        $this->assertContains('- Last sent at: 2026-03-05 11:00:00', $mail->introLines);
    }
}
