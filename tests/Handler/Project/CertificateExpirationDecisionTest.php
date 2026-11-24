<?php

namespace Uplinkr\Tests\Handler\Project\Alerts;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Uplinkr\Handler\Project\Alerts\CertificateExpirationDecision;
use Uplinkr\Tests\TestCase;

class CertificateExpirationDecisionTest extends TestCase
{
    private CertificateExpirationDecision $decision;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'UTC'));
        $this->decision = new CertificateExpirationDecision();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_is_disabled_by_default(): void
    {
        $this->assertNull($this->decision->decide(
            ['probe_tls_expiration_date' => '2026-12-01T00:00:00+00:00'],
            []
        ));
    }

    public function test_it_does_not_warn_outside_the_largest_threshold(): void
    {
        $this->assertNull($this->decision->decide(
            ['probe_tls_expiration_date' => '2027-01-01T00:00:00+00:00'],
            $this->enabledAlert()
        ));
    }

    #[DataProvider('thresholdProvider')]
    public function test_it_selects_the_most_urgent_reached_threshold(string $expirationDate, int $expectedThreshold): void
    {
        $result = $this->decision->decide(
            ['probe_tls_expiration_date' => $expirationDate],
            $this->enabledAlert()
        );

        $this->assertSame('certificate_expiration', $result['reason']);
        $this->assertSame($expectedThreshold, $result['threshold_days']);
    }

    public static function thresholdProvider(): array
    {
        return [
            '30 days' => ['2026-12-24T23:59:59+00:00', 30],
            'skipped to 14 days' => ['2026-12-05T23:59:59+00:00', 14],
            'skipped to 3 days' => ['2026-11-26T23:59:59+00:00', 3],
        ];
    }

    public function test_it_does_not_repeat_the_same_threshold_for_the_same_certificate(): void
    {
        $expirationDate = '2026-12-05T23:59:59+00:00';

        $this->assertNull($this->decision->decide([
            'probe_tls_expiration_date' => $expirationDate,
            'certificate_expiration_alert' => [
                'expiration_date' => $expirationDate,
                'last_notified_threshold_days' => 14,
                'last_notified_at' => '2026-11-24T08:00:00+00:00',
                'expired_notified_at' => null,
            ],
        ], $this->enabledAlert()));
    }

    public function test_it_warns_at_the_next_threshold_for_the_same_certificate(): void
    {
        $expirationDate = '2026-11-30T23:59:59+00:00';

        $result = $this->decision->decide([
            'probe_tls_expiration_date' => $expirationDate,
            'certificate_expiration_alert' => [
                'expiration_date' => $expirationDate,
                'last_notified_threshold_days' => 14,
            ],
        ], $this->enabledAlert());

        $this->assertSame(7, $result['threshold_days']);
    }

    public function test_it_warns_once_for_an_expired_certificate(): void
    {
        $expirationDate = '2026-11-23T23:59:59+00:00';
        $result = $this->decision->decide(
            ['probe_tls_expiration_date' => $expirationDate],
            $this->enabledAlert()
        );

        $this->assertSame('certificate_expired', $result['reason']);
        $this->assertSame(-1, $result['days_remaining']);
        $this->assertNull($result['threshold_days']);

        $this->assertNull($this->decision->decide([
            'probe_tls_expiration_date' => $expirationDate,
            'certificate_expiration_alert' => [
                'expiration_date' => $expirationDate,
                'expired_notified_at' => '2026-11-24T08:00:00+00:00',
            ],
        ], $this->enabledAlert()));
    }

    public function test_a_changed_expiration_date_starts_a_new_warning_cycle(): void
    {
        $result = $this->decision->decide([
            'probe_tls_expiration_date' => '2026-12-05T23:59:59+00:00',
            'certificate_expiration_alert' => [
                'expiration_date' => '2026-11-30T23:59:59+00:00',
                'last_notified_threshold_days' => 14,
            ],
        ], $this->enabledAlert());

        $this->assertSame('certificate_expiration', $result['reason']);
        $this->assertSame(14, $result['threshold_days']);
    }

    public function test_it_ignores_missing_and_invalid_expiration_dates(): void
    {
        $this->assertNull($this->decision->decide([], $this->enabledAlert()));
        $this->assertNull($this->decision->decide(
            ['probe_tls_expiration_date' => 'not-a-date'],
            $this->enabledAlert()
        ));
    }

    private function enabledAlert(): array
    {
        return [
            'certificate_expiration_enabled' => true,
            'certificate_expiration_warning_days' => [30, 14, 7, 3, 1],
        ];
    }
}
