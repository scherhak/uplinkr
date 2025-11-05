<?php

namespace Uplinkr\Handler;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Uplinkr\Interfaces\StorageInterface;

/**
 * Class ProbeUriHandler
 * @package Uplinkr\Handler
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 *
 * This class is responsible for handling and probing a given URI to determine its reachability,
 * processing the response, and logging the outcome along with relevant metadata.
 */
class ProbeUriHandler
{
    /**
     * @var array $data
     */
    private array $data;

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
     * @return void Does not return a value.
     */
    public function handle(): void
    {
        $request = null;
        $status = 'not-reachable';

        $startTime = microtime(true);

        try {
            $request = Http::withHeaders([
                'User-Agent' => 'uplinkr-probe/1.0',
            ])->head($this->getUriFromData());

            if ($request->successful()) {
                $status = 'reachable';
                $probeMessage = [
                    'message' => 'Uri currently reachable',
                    'lang_key' => 'uri.reachable',
                ];
            } else {
                // z. B. 404, 500 usw.
                $status = 'unreachable';
                $probeMessage = [
                    'message' => 'Non-200 status code: ' . $request->status(),
                    'lang_key' => 'uri.unreachable',
                ];
            }
        } catch (ConnectionException $ce) {
            $probeMessage = [
                'message' => 'fatale: ' . $ce->getMessage(),
                'lang_key' => 'uri.fatal',
            ];
        }

        $result = $this->getRequestResult($request);

        $durationTime = microtime(true) - $startTime;
        $probeMessage = Arr::add($probeMessage, 'duration_ms', round($durationTime * 1000, 2));
        $probeMessage = Arr::add($probeMessage, 'duration_s', round($durationTime, 2));

        $result = Arr::add($result, 'time_to_load', $durationTime);
        $result = Arr::add($result, 'probe_message', $probeMessage);
        $result = Arr::add($result, 'status', $status);
        $result = Arr::add($result, 'executed', now());
        $result = Arr::add($result, 'settings', [
            'protocol' => Arr::get($this->data, 'protocol'),
            'uri' => Arr::get($this->data, 'uri'),
        ]);

        app(StorageInterface::class)->saveResult($result);

        // currently only in development mode
        Log::info('ProbeUriHandler::execute', [
            'data' => $this->data,
            'uri' => $this->getUriFromData(),
            'result' => $result,
            'probeMessage' => $probeMessage,
        ]);
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
     * @return string
     */
    private function getUriFromData(): string
    {
        return sprintf('%s://%s',
            Arr::get($this->data, 'protocol'),
            Arr::get($this->data, 'uri')
        );
    }
}
