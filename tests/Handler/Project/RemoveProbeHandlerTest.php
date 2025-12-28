<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Project\RemoveProbeHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class RemoveProbeHandlerTest extends TestCase
{
    public function test_handle_calls_storage_remove_from_project(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $options = [
            'url' => 'http://example.com',
            'project' => 'my-project'
        ];

        $storageMock->shouldReceive('removeFromProject')
            ->once()
            ->with($options);

        $handler = new RemoveProbeHandler($storageMock);
        $result = $handler->handle($options);

        $this->assertTrue($result);
    }
}
