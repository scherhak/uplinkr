<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Project\AddProbeHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class AddProbeHandlerTest extends TestCase
{
    public function test_handle_calls_storage_add_to_project(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $options = [
            'url' => 'http://example.com',
            'project' => 'my-project',
            'method' => 'GET',
            'headers' => [],
            'body' => '',
            'latency' => 500
        ];

        $storageMock->shouldReceive('addToProject')
            ->once()
            ->with($options);

        $handler = new AddProbeHandler($storageMock);
        $result = $handler->handle($options);

        $this->assertTrue($result);
    }
}
