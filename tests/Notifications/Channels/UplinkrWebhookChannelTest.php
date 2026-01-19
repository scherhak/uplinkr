<?php

namespace Uplinkr\Tests\Notifications\Channels;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Mockery;
use Uplinkr\Notifications\Channels\UplinkrWebhookChannel;
use Uplinkr\Tests\TestCase;

class UplinkrWebhookChannelTest extends TestCase
{
    public function test_it_sends_notification_via_webhook(): void
    {
        $channel = new UplinkrWebhookChannel();
        $notifiable = new AnonymousNotifiable();
        
        $notification = new class extends Notification {
            public bool $toWebhookCalled = false;
            public function toWebhook($notifiable) {
                $this->toWebhookCalled = true;
            }
        };

        $channel->send($notifiable, $notification);
        
        $this->assertTrue($notification->toWebhookCalled); 
    }

    public function test_it_skips_if_towebhook_method_is_missing(): void
    {
        $channel = new UplinkrWebhookChannel();
        $notifiable = new AnonymousNotifiable();
        
        $notification = new class extends Notification {
            // no toWebhook method
        };

        $channel->send($notifiable, $notification);
        
        $this->assertTrue(true);
    }
}
