<?php

namespace Uplinkr\Handler;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

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

    /**
     * @param StorageInterface $storage
     * @param UplinkrConfig $config
     * @param Sanitizer $sanitizer
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly UplinkrConfig    $config,
        private readonly Sanitizer        $sanitizer
    )
    {
    }

    /**
     * @param array $data The data to be set for the handler.
     * @return static Returns the current instance with the updated data.
     */
    public function with(array $data): static
    {
        $this->data = $data;

        Log::debug('ProbeApiHandler with data: ', [
            'data' => $this->data,
        ]);

        return $this;
    }

    /**
     * Handles the API probe process, including sending an HTTP request, measuring execution time,
     * analyzing the response, composing the result, and saving the probe result in storage.
     *
     * @return array|null Returns the result array of the probe operation, or null if the result cannot be determined.
     */
    public function handle(): ?array
    {
        $request = null;
        $startTime = microtime(true);
        $method = strtoupper($this->getMethod());
        $endpoint = $this->getEndpoint();

        // set probe status default to error
        $probeStatus = 'error';

        try {
            $pendingRequest = Http::withHeaders([
                'User-Agent' => 'uplinkr-api-probe-0.1.0',
            ])->withHeaders($this->getParsedHeaders());

            // Body handling for POST/PUT/PATCH
            $body = $this->getBody();
            if ($body && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $content = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
                $pendingRequest->withBody($content, contentType: 'application/json');
            }

            $request = $pendingRequest->send($method, $endpoint);

            if ($request->successful()) {
                $probeStatus = 'reachable';
                $probeMessage = [
                    'lang_key' => 'messages.api_reachable',
                ];
            } else {
                $probeStatus = 'unreachable';
                $probeMessage = [
                    'lang_key' => 'messages.api_unreachable',
                ];
            }
        } catch (ConnectionException $ce) {
            $probeMessage = [
                'exception' => $ce->getMessage(),
                'lang_key' => 'messages.api_error',
            ];
        } catch (Exception $e) {
            $probeMessage = [
                'exception' => $e->getMessage(),
                'lang_key' => 'messages.api_error',
            ];
        }

        $durationTime = microtime(true) - $startTime;

        // build the result ...
        $result = $this->buildProbeResult(
            request: $request,
            durationTime: $durationTime,
            probeMessage: $probeMessage,
            probeStatus: $probeStatus
        );

        // ... and finally save it
        $this->storage->saveResult(resultData: $result);

        return $result;
    }

    /**
     * @param mixed $request The original request data used for building the probe result.
     * @param float $durationTime The time duration associated with the probe in seconds.
     * @param array $probeMessage Messages or details related to the probe process.
     * @param string $probeStatus The status of the probe, such as success or failure.
     * @return array The resulting probe data structured and built for further processing.
     */
    private function buildProbeResult(mixed $request, float $durationTime, array $probeMessage, string $probeStatus): array
    {
        $requestResult = $this->getRequestResult($request);

        return (new ProbeResultHandler($requestResult))->build(
            durationTime: $durationTime,
            probeMessage: $probeMessage,
            probeStatus: $probeStatus,
            settings: [
                'endpoint' => $this->getEndpoint(),
                'project' => $this->getProject(),
            ]
        );
    }

    /**
     * Retrieves the result of a given request in the form of an associative array.
     *
     * @param mixed $request The request object from which the result will be extracted. Can be null.
     * @return array An associative array containing the status header, headers, and body of the request, or an empty array if the request is null.
     */
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

    /**
     * Retrieves the HTTP method from the data array or defaults to 'GET' if not found.
     *
     * @return string The HTTP method retrieved from the data array or the default value 'GET'.
     */
    private function getMethod(): string
    {
        return Arr::get($this->data, 'method', 'GET');
    }

    /**
     * Parses and retrieves headers from the data array.
     *
     * @return array An associative array of parsed headers where the keys are header names and the values are header values.
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

    /**
     * Retrieves the value associated with the 'body' key from the data array.
     *
     * @return string|array|null The value of the 'body' key, or null if the key does not exist.
     */
    private function getBody(): string|array|null
    {
        return Arr::get($this->data, 'body');
    }

    /**
     * Retrieves and sanitizes the project name from the data.
     *
     * @return string The sanitized project name.
     */
    private function getProject(): string
    {
        $project = Arr::get(
            $this->data,
            'project',
            $this->config->getStandardProject()
        );

        return $this->sanitizer->project($project);
    }

    /**
     * Retrieves the endpoint value from the data array.
     *
     * @return string The endpoint value extracted from the data.
     */
    private function getEndpoint(): string
    {
        return Arr::get($this->data, 'endpoint');
    }
}