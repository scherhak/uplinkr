<?php

namespace Handler\Project;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            'probe_tls_expiration_date' => '2027-01-01T00:00:00+00:00',
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
            $payload = array_merge($alertData, ['probe_tls_expiration_date' => null]);
            $expectedSignature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
            return $request->hasHeader('X-Signature', $expectedSignature);
        });
    }

    public function test_to_array_contains_probe_tls_expiration_date_key_even_when_missing(): void
    {
        $notification = new AlertNotificationHandler([
            'project' => 'No TLS Project',
        ]);

        $payload = $notification->toArray(null);

        $this->assertArrayHasKey('probe_tls_expiration_date', $payload);
        $this->assertNull($payload['probe_tls_expiration_date']);
    }

    public function test_to_log_includes_probe_tls_expiration_date_in_message_and_context(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'TLS expiration date: 2027-01-01T00:00:00+00:00')
                    && ($context['probe_tls_expiration_date'] ?? null) === '2027-01-01T00:00:00+00:00';
            });

        $notification = new AlertNotificationHandler([
            'project' => 'Test Project',
            'probe' => 'GET https://example.com',
            'reason' => 'consecutive_failures',
            'count' => 3,
            'probe_tls_expiration_date' => '2027-01-01T00:00:00+00:00',
        ]);

        $notification->toLog(null);
    }

    public function test_to_log_uses_aggregated_message_for_multiple_probes(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'on 2 probes.')
                    && isset($context['probes'])
                    && count($context['probes']) === 2;
            });

        $notification = new AlertNotificationHandler([
            'project' => 'Aggregated Project',
            'alert' => ['channels' => ['log']],
            'probes' => [
                [
                    'probe' => 'GET https://example.com',
                    'reason' => 'consecutive_failures',
                    'count' => 3,
                    'probe_tls_expiration_date' => '2027-01-01T00:00:00+00:00',
                ],
                [
                    'probe' => 'GET https://example.org',
                    'reason' => 'consecutive_failures',
                    'count' => 4,
                    'probe_tls_expiration_date' => null,
                ],
            ],
        ]);

        $notification->toLog(null);
    }

    public function test_to_array_sorts_aggregated_probes_by_probe_name(): void
    {
        $notification = new AlertNotificationHandler([
            'project' => 'Sorted Project',
            'alert' => ['channels' => ['webhook']],
            'probes' => [
                ['probe' => 'GET https://z.example.com', 'count' => 3],
                ['probe' => 'GET https://a.example.com', 'count' => 2],
            ],
        ]);

        $payload = $notification->toArray(null);

        $this->assertSame('GET https://a.example.com', $payload['probes'][0]['probe']);
        $this->assertSame('GET https://z.example.com', $payload['probes'][1]['probe']);
    }

}
