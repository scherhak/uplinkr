<?php

namespace Uplinkr\Tests\Storage;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileProjectStorage;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class FileProjectStorageTest extends TestCase
{
    private FileProjectStorage $storage;
    private UplinkrConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            standardProject: 'test_project',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($this->config);
        $this->storage = new FileProjectStorage($this->config, $sanitizer);
    }

    public function test_it_adds_probe_to_project(): void
    {
        // 1. Setup existing project
        $projectData = [
            'project' => 'my-test-project',
            'label' => 'My Test Project',
            'description' => 'This is a test project',
            'created_at' => '2025-12-26 11:56:15',
            'updated_at' => '2025-12-26 11:56:15',
            'probes' => []
        ];
        $this->storage->saveProject($projectData);

        // 2. Add a probe
        $probeData = [
            'url' => 'http://example.com',
            'project' => 'my-test-project',
            'method' => 'GET',
            'headers' => ['Authorization' => 'Bearer test'],
            'body' => '{"foo":"bar"}'
        ];
        $this->storage->addToProject($probeData);

        // 3. Verify
        $updatedProject = $this->storage->findProject('my-test-project');
        $this->assertCount(1, $updatedProject['probes']);
        $this->assertEquals('http://example.com', $updatedProject['probes'][0]['url']);
        $this->assertEquals(['Authorization' => 'Bearer test'], $updatedProject['probes'][0]['header']);
        $this->assertEquals('{"foo":"bar"}', $updatedProject['probes'][0]['body']);
    }

    public function test_it_updates_existing_probe(): void
    {
        // 1. Setup project with existing probe
        $projectData = [
            'project' => 'my-test-project',
            'probes' => [
                [
                    'url' => 'http://example.com',
                    'project' => 'my-test-project',
                    'method' => 'GET',
                    'header' => null,
                    'body' => null
                ]
            ]
        ];
        $this->storage->saveProject($projectData);

        // 2. Update the probe
        $probeData = [
            'url' => 'http://example.com',
            'project' => 'my-test-project',
            'method' => 'POST',
            'headers' => ['X-Test' => 'value'],
            'body' => 'test body'
        ];
        $this->storage->addToProject($probeData);

        // 3. Verify
        $updatedProject = $this->storage->findProject('my-test-project');
        $this->assertCount(1, $updatedProject['probes']);
        $this->assertEquals('POST', $updatedProject['probes'][0]['method']);
        $this->assertEquals(['X-Test' => 'value'], $updatedProject['probes'][0]['header']);
        $this->assertEquals('test body', $updatedProject['probes'][0]['body']);
    }

    public function test_it_removes_probe_from_project(): void
    {
        // 1. Setup project with probes
        $projectData = [
            'project' => 'my-test-project',
            'probes' => [
                [
                    'url' => 'http://example.com',
                    'project' => 'my-test-project',
                ],
                [
                    'url' => 'http://other.com',
                    'project' => 'my-test-project',
                ]
            ]
        ];
        $this->storage->saveProject($projectData);

        // 2. Remove a probe
        $this->storage->removeFromProject([
            'url' => 'http://example.com',
            'project' => 'my-test-project'
        ]);

        // 3. Verify
        $updatedProject = $this->storage->findProject('my-test-project');
        $this->assertCount(1, $updatedProject['probes']);
        $this->assertEquals('http://other.com', $updatedProject['probes'][0]['url']);
    }

    public function test_it_retrieves_all_projects(): void
    {
        // 1. Setup multiple projects
        $project1 = ['project' => 'project-1', 'label' => 'Project 1'];
        $project2 = ['project' => 'project-2', 'label' => 'Project 2'];
        
        $this->storage->saveProject($project1);
        $this->storage->saveProject($project2);

        // 2. Retrieve all projects
        $allProjects = $this->storage->allProjects();

        // 3. Verify
        $this->assertCount(2, $allProjects);
        
        $projectNames = array_column($allProjects, 'project');
        $this->assertContains('project-1', $projectNames);
        $this->assertContains('project-2', $projectNames);
    }
}
