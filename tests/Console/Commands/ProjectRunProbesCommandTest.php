<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ProbeAllProjectsHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProjectRunProbesCommandTest extends TestCase
{
    private MockInterface|ProbeAllProjectsHandler $handlerMock;
    private MockInterface|ProjectStorageInterface $storageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handlerMock = Mockery::mock(ProbeAllProjectsHandler::class);
        $this->storageMock = Mockery::mock(ProjectStorageInterface::class);
        $this->app->instance(ProbeAllProjectsHandler::class, $this->handlerMock);
        $this->app->instance(ProjectStorageInterface::class, $this->storageMock);
    }

    public function test_it_runs_probes_for_all_projects_with_confirmation(): void
    {
        $projectData = [
            ['project' => 'project1', 'probes' => [['url' => 'http://test.com']]],
        ];

        $this->storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn($projectData);

        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->handlerMock->shouldReceive('handleProject')
            ->once()
            ->with($projectData[0], Mockery::on(function($callback) use ($result) {
                if (is_callable($callback)) {
                    $callback($result, 'project1');
                }
                return true;
            }))
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-probes')
            ->expectsConfirmation('Should all probes for all projects be executed?', 'yes')
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Running 1 probes for project project1...')
            ->expectsOutput('Target URL is currently reachable (Response time: 100 ms)')
            ->expectsOutput('Result stored successfully in project project1.')
            ->expectsOutput('All probes have been executed.')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_runs_probes_with_force_option(): void
    {
        $projectData = [
            ['project' => 'project1', 'probes' => [['url' => 'http://test.com']]],
        ];

        $this->storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn($projectData);

        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->handlerMock->shouldReceive('handleProject')
            ->once()
            ->with($projectData[0], Mockery::on(function($callback) use ($result) {
                if (is_callable($callback)) {
                    $callback($result, 'project1');
                }
                return true;
            }))
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-probes', ['--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Running 1 probes for project project1...')
            ->expectsOutput('Target URL is currently reachable (Response time: 100 ms)')
            ->expectsOutput('Result stored successfully in project project1.')
            ->expectsOutput('All probes have been executed.')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_shows_warning_when_no_projects_found(): void
    {
        $this->storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn([]);

        $this->storageMock->shouldReceive('getStoragePath')
            ->once()
            ->andReturn('uplinkr-path');

        $this->artisan('uplinkr:project:run-probes', ['--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('No projects found in uplinkr-path.')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_aborts_on_confirmation_decline(): void
    {
        $this->handlerMock->shouldNotReceive('handle');

        $this->artisan('uplinkr:project:run-probes')
            ->expectsConfirmation('Should all probes for all projects be executed?', 'no')
            ->expectsOutput('The process was aborted.')
            ->assertExitCode(CommandAlias::INVALID);
    }

    public function test_it_skips_disabled_projects_and_shows_error(): void
    {
        $projectData = [
            ['project' => 'project1', 'status' => 'disabled', 'probes' => [['url' => 'http://test.com']]],
            ['project' => 'project2', 'status' => 'enabled', 'probes' => [['url' => 'http://test2.com']]],
        ];

        $this->storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn($projectData);

        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        // Should only be called for project2
        $this->handlerMock->shouldReceive('handleProject')
            ->once()
            ->with($projectData[1], Mockery::any())
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-probes', ['--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Project project1 is disabled.')
            ->expectsOutput('Running 1 probes for project project2...')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_shows_error_for_projects_with_missing_settings(): void
    {
        $projectData = [
            null,
            ['project' => 'project2', 'status' => 'enabled', 'probes' => [['url' => 'http://test2.com']]],
        ];

        $this->storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn($projectData);

        $this->storageMock->shouldReceive('allProjectDirectories')
            ->once()
            ->andReturn(['/path/to/project1', '/path/to/project2']);

        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->handlerMock->shouldReceive('handleProject')
            ->once()
            ->with($projectData[1], Mockery::any())
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-probes', ['--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Project project1 not found.')
            ->expectsOutput('Running 1 probes for project project2...')
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
