<?php

namespace Uplinkr\Traits;

use Illuminate\Support\Arr;

/**
 * Shared probe execution logic for project handlers.
 */
trait RunsProjectProbes
{
    /**
     * Executes probes for a project and returns the results.
     *
     * @param array $probes
     * @param string $projectName
     * @param callable|null $callback
     * @return array
     */
    private function runProbes(array $probes, string $projectName, ?callable $callback = null): array
    {
        $results = [];

        foreach ($probes as $probe) {
            $result = $this->urlHandler->with(data: [
                'url' => Arr::get($probe, 'url'),
                'project' => $projectName,
                'method' => Arr::get($probe, 'method', 'GET'),
                'headers' => Arr::get($probe, 'headers', []),
                'body' => Arr::get($probe, 'body', ''),
                'tls' => Arr::get($probe, 'tls', []),
            ])->handle();

            $results[] = $result;

            if ($callback) {
                $callback($result, $projectName);
            }
        }

        return $results;
    }
}
