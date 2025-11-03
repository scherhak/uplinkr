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
    public function __construct(
        private readonly array $data
    )
    {
    }

    /**
     * Executes the URI probe process to determine its reachability.
     *
     * The method performs an HTTP head request to the given URI, measures the time
     * taken to perform this request, and evaluates the reachability status. It
     * builds a probe result based on the response and logs the outcome.
     *
     * @return void Does not return a value.
     * @throws JsonException
     */
    public function execute(): void
    {
        $request = null;
        $status = 'not-reachable';
        $probeMessage = [
            'message' => 'Uri currently not reachable',
            'lang_key' => 'uri.not-reachable',
        ];

        $startTime = microtime(true);

        try {
            $request = Http::withHeaders([
                'User-Agent' => 'uplinkr-probe/1.0',
            ])->head($this->getUriFromData());

            if (200 === $request->getStatusCode()) {
                $status = 'reachable';
                $probeMessage = [
                    'message' => 'Uri currently reachable',
                    'lang_key' => 'uri.reachable',
                ];
            }
        } catch (ConnectionException $ce) {
            $probeMessage = [
                'message' => $ce->getMessage(),
                'lang_key' => 'uri.not-reachable',
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
