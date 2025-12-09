<?php

namespace Uplinkr\Handler\Probe;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
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
     * @param StorageInterface $storage An instance of StorageInterface to handle storage operations.
     * @return void
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly UplinkrConfig    $config,
        private readonly Sanitizer        $sanitizer
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
     * Executes the URI probe process to determine its reachability.
     *
     * The method performs an HTTP head request to the given URI, measures the time
     * taken to perform this request, and evaluates the reachability status. It
     * builds a probe result based on the response and logs the outcome.
     *
     * @return array|null Does not return a value.
     */
    public function handle(): ?array
    {
        $request = null;
        $startTime = microtime(as_float: true);

        try {
            $request = Http::withHeaders([
                'User-Agent' => 'uplinkr-url-probe-0.1.0',
            ])->head($this->getUrl());

            if ($request->successful()) {
                $probeStatus = 'reachable';
                $probeMessage = [
                    'lang_key' => 'messages.url_reachable',
                ];
            } else {
                $probeStatus = 'unreachable';
                $probeMessage = [
                    'lang_key' => 'messages.url_unreachable',
                ];
            }
        } catch (ConnectionException $ce) {
            $probeStatus = 'error';
            $probeMessage = [
                'exception' => $ce->getMessage(),
                'lang_key' => 'messages.url_error',
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
     */
    private function buildProbeResult(mixed $request, float $durationTime, array $probeMessage, string $probeStatus): array
    {
        $requestResult = $this->getRequestResult($request);

        return (new ResultHandler($requestResult))->build(
            durationTime: $durationTime,
            probeMessage: $probeMessage,
            probeStatus: $probeStatus,
            settings: [
                'url' => $this->getUrl(),
                'project' => $this->getProject(),
            ]
        );
    }

    /**
     * Processes the given response and extracts relevant data into an array.
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
        $project = Arr::get(
            $this->data,
            'project',
            $this->config->getStandardProject()
        );

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
}
