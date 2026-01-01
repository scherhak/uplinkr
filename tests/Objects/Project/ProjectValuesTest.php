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
}
