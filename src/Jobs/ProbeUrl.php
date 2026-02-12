<?php

namespace Uplinkr\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JsonException;
use Uplinkr\Handler\Probe\UrlHandler;

/**
 * Class ProbeUrl
 * @package Uplinkr\Jobs
 *
 * Job to execute URL probe checks asynchronously via queue.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Constructor.
     *
     * @param array $data The probe data (url, project, method, headers, body, etc.)
     */
    public function __construct(
        private readonly array $data
    )
    {
    }

    /**
     * Execute the job.
     *
     * @param UrlHandler $urlHandler
     * @return void
     * @throws JsonException
     */
    public function handle(UrlHandler $urlHandler): void
    {
        // Force direct execution when called from job context
        // This prevents infinite loop when execution_mode is 'job'
        $urlHandler->with($this->data)->executeProbe();
    }
}
