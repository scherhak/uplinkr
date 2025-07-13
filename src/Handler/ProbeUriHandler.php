<?php

namespace Uplinkr\Handler;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class ProbeUriHandler
 * @package Uplinkr\Handler
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 *
 * This class is responsible for handling and probing a given URI to determine its reachability,
 *  processing the response, and logging the outcome along with relevant metadata.
 */
readonly class ProbeUriHandler
{
    public function __construct(
        private array $data
    )
    {
    }

    /**
     * @throws \JsonException
     */
    public function execute()
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

            if(200 === $request->getStatusCode()) {
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

        $durationTime = microtime(true) - $startTime;
        $probeMessage = Arr::add($probeMessage, 'duration_ms', round($durationTime * 1000, 2));
        $probeMessage = Arr::add($probeMessage, 'duration_s', round($durationTime, 2));

        $result = $this->buildProbeResult($request);
        $result = Arr::add($result, 'time_to_load', $durationTime);
        $result = Arr::add($result, 'probe_message', json_encode($probeMessage, JSON_THROW_ON_ERROR));
        $result = Arr::add($result, 'status', $status);

//        (new ProbeResultHandler($result))->store();

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
    private function buildProbeResult(mixed $request): array
    {
        if(null !== $request) {
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
