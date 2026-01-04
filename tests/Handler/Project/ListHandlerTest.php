<?php

namespace Handler\Project;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class ListHandlerTest extends TestCase
{
    private UplinkrConfig $config;
    private MockInterface|Filesystem $filesystemMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr_projects',
            probeResultsPath: 'probes',
            archivedFolder: 'archive'
        );

        $this->filesystemMock = Mockery::mock(Filesystem::class);
    }

    public function testListAllReturnsDirectories(): void
    {
        $expectedDirectories = ['dir1', 'dir2'];

        Storage::shouldReceive('disk')
            ->with('local')
            ->once()
            ->andReturn($this->filesystemMock);

        $this->filesystemMock->shouldReceive('directories')
            ->with('uplinkr_projects')
            ->once()
            ->andReturn($expectedDirectories);

        $handler = new ListHandler($this->config);

        $this->assertEquals($expectedDirectories, $handler->all());
    }

    public function testGetProbesCountReturnsCountOfFiles(): void
    {
        $path = 'some/project/path';

        Storage::shouldReceive('disk')
            ->with('local')
            ->once()
            ->andReturn($this->filesystemMock);

        $files = ['file1.json', 'file2.json', 'file3.json'];

        $this->filesystemMock->shouldReceive('allFiles')
            ->with($path . '/probes')
            ->once()
            ->andReturn($files);

        $handler = new ListHandler($this->config);

        $this->assertEquals(3, $handler->countProbes($path));
    }
}
