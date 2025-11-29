<?php

namespace Uplinkr\Handler;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ProbeApiHandler
 * @package Uplinkr\Handler
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeApiHandler
{
    /**
     * @var array $data
     */
    private array $data;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly UplinkrConfig    $config
    )
    {
    }

    public function with(array $data): static
    {
        $this->data = $data;

        Log::debug('ProbeApiHandler with data: ', [
            'data' => $this->data,
        ]);

        return $this;
    }

    public function handle(): ?array
    {
        $request = null;
        $startTime = microtime(true);
        $method = strtoupper($this->getMethod());
        $endpoint = $this->getEndpoint();

        try {
            $pendingRequest = Http::withHeaders([
                'User-Agent' => 'uplinkr-api-probe-0.1.0',
            ])->withHeaders($this->getParsedHeaders());

            // Body Handling für POST/PUT/PATCH
            $body = $this->getBody();
            if ($body && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $content = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
                $pendingRequest->withBody($content, contentType: 'application/json');
            }

            // Universeller Aufruf der Methode
            $request = $pendingRequest->send($method, $endpoint);

            $probeMessage = [

            ];

            if ($request->successful()) {
                $status = 'reachable';
                $probeMessage = [
                    'message' => 'Uri currently reachable',
                    'lang_key' => 'messages.api_reachable',
                ];
            } else {
                $status = 'unreachable';
                $probeMessage = [
                    'message' => 'Non-200 status code: ' . $request->status(),
                    'lang_key' => 'messages.api_unreachable',
                ];
            }
        } catch (ConnectionException $ce) {
            $status = 'error';
            $probeMessage = [
                'message' => 'fatal: ' . $ce->getMessage(),
                'lang_key' => 'messages.api_error',
            ];
        } catch (Exception $e) {
            $status = 'error';
            $probeMessage = [
                'message' => 'fatal: ' . $e->getMessage(),
                'lang_key' => 'messages.api_error',
            ];
        }

        $durationTime = microtime(true) - $startTime;

        // build the result ...
        $result = $this->buildProbeResult(
            request: $request,
            durationTime: $durationTime,
            probeMessage: $probeMessage,
            status: $status
        );

        // ... and finally save it
        $this->storage->saveResult(resultData: $result);

        return $result;
    }

    private function buildProbeResult(mixed $request, float $durationTime, array $probeMessage, string $status): array
    {
        $requestResult = $this->getRequestResult($request);

        return (new ProbeResultHandler($requestResult))->build(
            durationTime: $durationTime,
            probeMessage: $probeMessage,
            status: $status,
            settings: [
                'endpoint' => $this->getEndpoint(),
                'project' => $this->getProject(),
            ]
        );
    }

    private function getRequestResult(mixed $request): array
    {
        if (null !== $request) {
            return [
                'status_header' => $request->getStatusCode(),
                'headers' => $request->headers(),
                'body' => $request->body(),
            ];
        }

        return [];
    }

    private function getMethod(): string
    {
        return Arr::get($this->data, 'method', 'GET');
    }

    /**
     * Parst die Header von ["Key: Value"] in ["Key" => "Value"]
     */
    private function getParsedHeaders(): array
    {
        $headers = Arr::get($this->data, 'headers', []);
        $parsed = [];

        foreach ($headers as $header) {
            if (is_string($header) && str_contains($header, ':')) {
                [$key, $value] = explode(':', $header, 2);
                $parsed[trim($key)] = trim($value);
            }
        }

        return $parsed;
    }

    private function getBody(): string|array|null
    {
        return Arr::get($this->data, 'body');
    }

    private function getProject(): string
    {
        $project = Arr::get(
            $this->data,
            'project',
            $this->config->getStandardProject()
        );

        return $this->sanitizeProjectName($project);
    }

    private function getEndpoint(): string
    {
        return Arr::get($this->data, 'endpoint');
    }

    private function sanitizeProjectName(string|null $value): string
    {
        if ($value === null) {
            return $this->config->getStandardProject();
        }

        // Replace problematic characters with hyphens (including dot)
        // - Remove control characters
        // - Reduce multiple hyphens to a single one
        // - Replace whitespace with hyphens
        // - Trim hyphens and whitespace
        $sanitized = preg_replace('/[\/\\\:*?"<>|()!$%&.]/', '-', $value);
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $sanitized);
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        $sanitized = preg_replace('/\s+/', '-', $sanitized);
        $sanitized = trim($sanitized, '- ');

        return strtolower($sanitized);
    }
}