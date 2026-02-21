<?php

namespace Uplinkr\Tests\Objects\Project;

use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Tests\TestCase;

class ProjectValuesTest extends TestCase
{
    public function test_getStatus_returns_status_if_set(): void
    {
        $values = new ProjectValues(['status' => 'disabled']);
        $this->assertEquals('disabled', $values->getStatus());

        $values = new ProjectValues(['status' => 'enabled']);
        $this->assertEquals('enabled', $values->getStatus());
    }

    public function test_getStatus_returns_enabled_by_default(): void
    {
        $values = new ProjectValues([]);
        $this->assertEquals('enabled', $values->getStatus());
    }

    public function test_getName_returns_project_name(): void
    {
        $values = new ProjectValues(['project' => 'my-project']);
        $this->assertEquals('my-project', $values->getName());
    }

    public function test_getProbes_normalizes_legacy_header_key_to_headers(): void
    {
        $values = new ProjectValues([
            'probes' => [
                [
                    'url' => 'https://example.com',
                    'header' => ['Authorization: Bearer test'],
                ],
            ],
        ]);

        $probes = $values->getProbes();

        $this->assertEquals(['Authorization: Bearer test'], $probes[0]['headers']);
        $this->assertEquals(['Authorization: Bearer test'], $probes[0]['header']);
    }
}
