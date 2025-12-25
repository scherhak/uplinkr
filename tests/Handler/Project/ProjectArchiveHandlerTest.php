<?php

namespace Handler\Project;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ArchiveHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class ProjectArchiveHandlerTest extends TestCase
{
    private UplinkrConfig $config;
    private MockInterface|Filesystem $filesystemMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Since UplinkrConfig is 'final', we're using a real instance.
        // We're overriding the default values with test values to ensure
        // that the handler uses the values from the config and not hardcoded values.
        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr_projects',
            probeResultsPath: 'probes',
            archivedFolder: 'archive'
        );

        // Filesystem Mock (the object that returns Storage::disk())
        $this->filesystemMock = Mockery::mock(Filesystem::class);
    }

    public function testExistsReturnsTrueIfProjectExists(): void
    {
        $projectName = 'test-project';
        
        // Expectation: Storage::disk() is called in the constructor.
        Storage::shouldReceive('disk')
            ->with('local')
            ->once()
            ->andReturn($this->filesystemMock);

        // Expectation: $storage->exists() will be called
        // The path is constructed from config->getStoragePath() + projectName
        $this->filesystemMock->shouldReceive('exists')
            ->with('uplinkr_projects/' . $projectName)
            ->once()
            ->andReturnTrue();

        $handler = new ArchiveHandler($this->config);
        
        $this->assertTrue($handler->exists($projectName));
    }

    public function testExistsReturnsFalseIfProjectMissing(): void
    {
        $projectName = 'missing-project';

        Storage::shouldReceive('disk')->andReturn($this->filesystemMock);
        
        $this->filesystemMock->shouldReceive('exists')
            ->with('uplinkr_projects/' . $projectName)
            ->andReturnFalse();

        $handler = new ArchiveHandler($this->config);

        $this->assertFalse($handler->exists($projectName));
    }

    public function testArchiveCopiesDirectoryToArchiveLocation(): void
    {
        $projectName = 'project-to-archive';
        
        Storage::shouldReceive('disk')->andReturn($this->filesystemMock);

        // Since getProjectPath and setArchivePath internally call $storage->path(), We need to mock this.
        $sourcePath = '/real/path/uplinkr_projects/' . $projectName;
        $destPath = '/real/path/uplinkr_projects/archive/' . $projectName;

        $this->filesystemMock->shouldReceive('path')
            ->with('uplinkr_projects/' . $projectName)
            ->andReturn($sourcePath);

        $this->filesystemMock->shouldReceive('path')
            ->with('uplinkr_projects/archive/' . $projectName)
            ->andReturn($destPath);

        // Mock for the file facade, which handles the actual copying.
        File::shouldReceive('copyDirectory')
            ->with($sourcePath, $destPath)
            ->once()
            ->andReturnTrue();

        $handler = new ArchiveHandler($this->config);

        $this->assertTrue($handler->archive($projectName));
    }

    public function testDeleteRemovesDirectoryRecursively(): void
    {
        $projectName = 'project-to-delete';

        // Storage::disk is called twice:
        // 1x in the constructor
        // 1x in the delete method itself
        Storage::shouldReceive('disk')
            ->with('local')
            ->times(2)
            ->andReturn($this->filesystemMock);

        $this->filesystemMock->shouldReceive('deleteDirectory')
            ->with($projectName)
            ->once()
            ->andReturnTrue();

        $handler = new ArchiveHandler($this->config);

        $this->assertTrue($handler->delete($projectName));
    }

}
