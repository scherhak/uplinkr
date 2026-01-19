<?php

namespace Handler\Project;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Uplinkr\Handler\Project\Alerts\AlertNotificationHandler;
use Uplinkr\Tests\TestCase;

class AlertNotificationHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_to_webhook_sends_http_request_when_enabled(): void
    {
        $alertData = [
            'project' => 'Test Project',
            'probe' => 'https://example.com',
            'reason' => 'consecutive_failures',
            'count' => 3,
            'alert' => [
                'channels' => ['webhook']
            ]
        ];

        Config::set('uplinkr.notifications.channels.webhook', [
            'enabled' => true,
            'url' => 'https://webhook.site/test',
            'method' => 'POST',
            'headers' => ['X-Custom-Header' => 'Value'],
            'verify_tls' => true,
        ]);
        Config::set('uplinkr.notifications.payload.version', 'v1');

        $notification = new AlertNotificationHandler($alertData);
        $notification->toWebhook(null);

        Http::assertSent(function ($request) use ($alertData) {
            return $request->url() === 'https://webhook.site/test' &&
                   $request->method() === 'POST' &&
                   $request->hasHeader('X-Custom-Header', 'Value') &&
                   $request['version'] === 'v1' &&
                   $request['data'] === $alertData;
        });
    }

    public function test_to_webhook_does_not_send_request_when_disabled(): void
    {
        Config::set('uplinkr.notifications.channels.webhook.enabled', false);

        $notification = new AlertNotificationHandler([]);
        $notification->toWebhook(null);

        Http::assertNothingSent();
    }

    public function test_to_webhook_adds_signature_when_enabled(): void
    {
        $alertData = ['project' => 'Signed Project'];
        $secret = 'test-secret';

        Config::set('uplinkr.notifications.channels.webhook', [
            'enabled' => true,
            'url' => 'https://webhook.site/signed',
            'method' => 'POST',
            'signing' => [
                'enabled' => true,
                'secret' => $secret,
                'header' => 'X-Signature',
                'algo' => 'sha256',
            ],
        ]);
        Config::set('uplinkr.notifications.payload.version', null);

        $notification = new AlertNotificationHandler($alertData);
        $notification->toWebhook(null);

        Http::assertSent(function ($request) use ($alertData, $secret) {
            $expectedSignature = 'sha256=' . hash_hmac('sha256', json_encode($alertData), $secret);
            return $request->hasHeader('X-Signature', $expectedSignature);
        });
    }

}
