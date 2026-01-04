<?php

namespace Uplinkr\Handler\Probe;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Support\Sanitizer;

/**
 * Class UrlHandler
 * @package Uplinkr\Handler
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class UrlHandler
{
    /**
     * @var array $data
     */
    private array $data;

    /**
     * Constructor method.
     *
     * @param ProbeResultsStorageInterface $storage An instance of ProbeResultsStorageInterface to handle storage operations.
     * @return void
     */
    public function __construct(
        private readonly ProbeResultsStorageInterface $storage,
        private readonly UplinkrConfig                $config,
        private readonly Sanitizer                    $sanitizer,
        private readonly ResultHandler                $resultHandler
    )
    {
    }

    /**
     * Sets the provided data into the object and returns the updated instance.
     *
     * @param array $data The associative array of data to be stored in the object.
     * @return static Returns the current instance with the updated data.
     */
    public function with(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Handles the processing of an HTTP request, including sending the request, handling response,
     * and building and saving the probe result.
     *
     * @return array|null The resulting data from the probe, or null if the process fails.
     * @throws JsonException
     */
    public function handle(): ?array
    {
        $request = null;
        $startTime = microtime(true);

        $method = Str::upper($this->getMethod());

        // default
        $probeStatus = 'error';

        try {
            $pendingRequest = Http::withHeaders([
                // TODO (0.1.0) Insert a proper and correct name for the URL check.
                'User-Agent' => 'uplinkr-url-probe-0.1.0',
            ])->withHeaders($this->getParsedHeaders());

            // Optional body for methods that support it
            $body = $this->getBody();
            if ($body && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $content = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
                $pendingRequest->withBody($content, contentType: 'application/json');
            }

            // Send request to URL
            $request = $pendingRequest->send($method, $this->getUrl());

            if ($request->successful()) {
                $probeStatus = 'reachable';
                $probeMessage = [
                    'lang_key' => 'messages.probe_reachable',
                ];
            } else {
                $probeStatus = 'unreachable';
                $probeMessage = [
                    'lang_key' => 'messages.probe_unreachable',
                ];
            }
        } catch (ConnectionException $ce) {
            $probeMessage = [
                'exception' => $ce->getMessage(),
                'lang_key' => 'messages.probe_error',
            ];
        } catch (Exception $e) {
            $probeMessage = [
                'exception' => $e->getMessage(),
                'lang_key' => 'messages.probe_error',
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
     * Builds the complete probe result by combining request data with metadata.
     *
     * @param mixed $request The HTTP request/response object
     * @param float $durationTime The time taken to perform the probe in seconds
     * @param array $probeMessage The probe message array containing message and lang_key
     * @param string $probeStatus The status of the probe (reachable, unreachable, not-reachable)
     * @return array The complete result array with all metadata
     * @throws JsonException
     */
    private function buildProbeResult(mixed $request, float $durationTime, array $probeMessage, string $probeStatus): array
    {
        $requestResult = $this->getRequestResult($request);

        return $this->resultHandler
            ->with(result: $requestResult)
            ->build(
                durationTime: $durationTime,
                probeMessage: $probeMessage,
                probeStatus: $probeStatus,
                settings: [
                    'url' => $this->getUrl(),
                    'project' => $this->getProject(),
                    'method' => $this->getMethod(),
                ]
            );
    }

    /**
     * Processes the given response and extracts relevant data into an array.
     *
     * TODO Consider whether the content of the page or the request plays a role in availability.
     * TODO If this is relevant, then it should appear in the command. ('body' => $request->body())
     *
     * @param mixed $request The response object containing status, headers, and optionally a body.
     * @return array Returns an array with the response status, headers, and optionally a body if specified.
     */
    private function getRequestResult(mixed $request): array
    {
        if (null !== $request) {
            return [
                'status_header' => $request->getStatusCode(),
                'headers' => $request->headers(),
            ];
        }

        return [];
    }

    /**
     * Retrieves the project name or identifier from the data array.
     *
     * @return string The value of the 'project' key from the data array.
     */
    private function getProject(): string
    {
        $projectValues = new ProjectValues($this->data);
        $project = $projectValues->getName();

        if ($project === 'unknown') {
            $project = $this->config->getStandardProject();
        }

        return $this->sanitizer->project($project);
    }

    /**
     * Retrieves the URI value from the data array.
     *
     * @return string The URI extracted from the data array.
     */
    private function getUrl(): string
    {
        return Arr::get($this->data, 'url');
    }

    /**
     * Retrieves the HTTP method from the data array or defaults to 'GET'.
     */
    private function getMethod(): string
    {
        return Arr::get($this->data, 'method', 'HEAD');
    }

    /**
     * Parses and returns headers provided as ["Key: Value", ...] to an assoc array.
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
     * Returns the optional request body.
     */
    private function getBody(): string|array|null
    {
        return Arr::get($this->data, 'body');
    }
}
