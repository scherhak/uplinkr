<?php

namespace Uplinkr\Handler;

use Uplinkr\Helper\IdsHelper;
use Illuminate\Support\Facades\Log;

readonly class ProbeResultHandler
{
    public function __construct(
        private array $data
    )
    {

    }

    /**
     * @throws \JsonException
     */
    public function store(): ?string
    {
        $probeId = IdsHelper::createPRBID();
//
        if($this->storeInResults($probeId)) {
//            Cache::put($probeId, $this->data);
//
//            if('reachable' !== Arr::get($this->data, 'status')) {
//                $this->storeInAlerts($probeId);
//            }

            return $probeId;
        }

        return null;
    }

    /**
     * @param string $probeId
     * @return bool
     * @throws \JsonException
     */
    private function storeInResults(string $probeId): bool
    {
//        $probeResult = new ProbeResults();
//        $probeResult->intv_id = Arr::get($this->data, 'intv_id');
//        $probeResult->prj_id = Arr::get($this->data, 'prj_id');
//        $probeResult->uri_id = Arr::get($this->data, 'uri_id');
//        $probeResult->prb_id = $probeId;
//        $probeResult->status_header = Arr::get($this->data, 'status_header');
//        $probeResult->headers = json_encode(Arr::get($this->data, 'headers', []), JSON_THROW_ON_ERROR);
//        $probeResult->time_to_load = Arr::get($this->data, 'time_to_load', 0.00);
//        $probeResult->probe_message = Arr::get($this->data, 'probe_message');
//        $probeResult->status = Arr::get($this->data, 'status');
//
//        return $probeResult->save();

        Log::info('ProbeResultHandler::storeInResults', [
            'data' => $this->data,
        ]);

        return true;
    }

    /**
     * Stores alert data for a specific probe into the database.
     *
     * @param string $probeId The unique identifier of the probe that triggered the alert.
     * @return void
     */
//    private function storeInAlerts(string $probeId): void
//    {
//        $alertId = IdsHelper::createALRTID();
//
//        $alert = new ProbeAlerts();
//        $alert->prj_id = Arr::get($this->data, 'prj_id');
//        $alert->uri_id = Arr::get($this->data, 'uri_id');
//        $alert->intv_id = Arr::get($this->data, 'intv_id');
//        $alert->prb_id = $probeId;
//        $alert->alrt_id = $alertId;
//        $alert->turns = 1;
//
//        $alert->save();
//    }
}
