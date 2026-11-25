<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Class CertificateExpirationDecision
 * @package Uplinkr\Handler\Project\Alerts
 *
 * Determines whether an HTTPS probe has entered a configured certificate
 * warning threshold while preventing duplicate decisions for the same
 * certificate and threshold.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class CertificateExpirationDecision
{
    /**
     * Evaluates the current certificate state against configured thresholds.
     *
     * @param array $probeData The persisted probe state including expiration and prior notification data.
     * @param array $alert The project alert configuration containing activation and warning thresholds.
     * @return array|null A normalized certificate decision, or null when no notification is due.
     */
    public function decide(array $probeData, array $alert): ?array
    {
        if (Arr::get($alert, 'certificate_expiration_enabled', false) !== true) {
            return null;
        }

        $expirationDate = Arr::get($probeData, 'probe_tls_expiration_date');
        if (!is_string($expirationDate) || trim($expirationDate) === '') {
            return null;
        }

        try {
            $expiration = Carbon::parse($expirationDate)->utc();
        } catch (Throwable) {
            return null;
        }

        $now = Carbon::now('UTC');
        $daysRemaining = (int)$now
            ->copy()
            ->startOfDay()
            ->diffInDays($expiration->copy()->startOfDay(), false);
        $status = Arr::get($probeData, 'certificate_expiration_alert', []);
        $sameCertificate = $this->isSameExpirationDate(
            Arr::get($status, 'expiration_date'),
            $expiration
        );
        $normalizedExpirationDate = $expiration->toIso8601String();

        if ($expiration->lessThanOrEqualTo($now)) {
            if ($sameCertificate && Arr::get($status, 'expired_notified_at') !== null) {
                return null;
            }

            return [
                'reason' => 'certificate_expired',
                'expiration_date' => $normalizedExpirationDate,
                'days_remaining' => $daysRemaining,
                'threshold_days' => null,
            ];
        }

        $threshold = $this->currentThreshold(
            $daysRemaining,
            Arr::get($alert, 'certificate_expiration_warning_days', [30, 14, 7, 3, 1])
        );

        if ($threshold === null) {
            return null;
        }

        if ($sameCertificate) {
            $lastNotifiedThreshold = Arr::get($status, 'last_notified_threshold_days');
            if (is_int($lastNotifiedThreshold) && $lastNotifiedThreshold <= $threshold) {
                return null;
            }
        }

        return [
            'reason' => 'certificate_expiration',
            'expiration_date' => $normalizedExpirationDate,
            'days_remaining' => $daysRemaining,
            'threshold_days' => $threshold,
        ];
    }

    /**
     * Resolves the most urgent configured threshold reached by the certificate.
     *
     * @param int $daysRemaining Calendar days remaining until certificate expiry.
     * @param mixed $thresholds Configured warning thresholds in days.
     * @return int|null The smallest reached threshold, or null outside all thresholds.
     */
    private function currentThreshold(int $daysRemaining, mixed $thresholds): ?int
    {
        if (!is_array($thresholds)) {
            return null;
        }

        $reached = array_filter(
            array_unique(array_map('intval', $thresholds)),
            static fn(int $threshold): bool => $threshold > 0 && $daysRemaining <= $threshold
        );

        return empty($reached) ? null : min($reached);
    }

    /**
     * Compares a persisted warning cycle with the currently observed certificate.
     *
     * @param mixed $storedExpirationDate Expiration timestamp stored in the previous warning status.
     * @param Carbon $expiration The currently observed certificate expiration timestamp.
     * @return bool True when both timestamps identify the same certificate expiry.
     */
    private function isSameExpirationDate(mixed $storedExpirationDate, Carbon $expiration): bool
    {
        if (!is_string($storedExpirationDate) || trim($storedExpirationDate) === '') {
            return false;
        }

        try {
            return Carbon::parse($storedExpirationDate)->utc()->equalTo($expiration);
        } catch (Throwable) {
            return false;
        }
    }
}
