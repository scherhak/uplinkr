<?php

namespace Uplinkr\Tests\Console\Commands;

use Illuminate\Support\Facades\Notification;
use Uplinkr\Notifications\IamAliveNotification;
use Uplinkr\Support\CliIcon;
use Uplinkr\Tests\TestCase;

class UplinkrIamAliveCommandTest extends TestCase
{
    public function test_it_sends_an_iam_alive_mail_notification(): void
    {
        Notification::fake();

        config()->set('uplinkr.notifications.channels.mail.enabled', true);
        config()->set('uplinkr.notifications.channels.mail.to', ['ops@example.com']);

        $this->artisan('uplinkr:iam-alive')
            ->expectsOutput(CliIcon::OK->label(__('uplinkr::messages.iam_alive_sent', ['count' => 1])))
            ->assertExitCode(0);

        Notification::assertSentOnDemand(IamAliveNotification::class);
    }

    public function test_it_skips_when_no_mail_recipients_are_configured(): void
    {
        Notification::fake();

        config()->set('uplinkr.notifications.channels.mail.enabled', true);
        config()->set('uplinkr.notifications.channels.mail.to', []);

        $this->artisan('uplinkr:iam-alive')
            ->expectsOutput(CliIcon::WARN->label(__('uplinkr::messages.iam_alive_no_recipients')))
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
