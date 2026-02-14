<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\Probes\ProbeSelectedProjectsHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Support\CliIcon;
use Uplinkr\Tests\TestCase;

class ProjectRunSelectedCommandTest extends TestCase
{
    private MockInterface|ProbeSelectedProjectsHandler $handlerMock;
    private MockInterface|ProjectStorageInterface $storageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handlerMock = Mockery::mock(ProbeSelectedProjectsHandler::class);
        $this->storageMock = Mockery::mock(ProjectStorageInterface::class);
        $this->app->instance(ProbeSelectedProjectsHandler::class, $this->handlerMock);
        $this->app->instance(ProjectStorageInterface::class, $this->storageMock);
    }

    public function test_it_runs_probes_for_selected_project_with_confirmation(): void
    {
        $projectName = 'project1';
        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(['project' => $projectName, 'status' => 'enabled']);

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::on(function ($callback) use ($result) {
                if (is_callable($callback)) {
                    $callback($result, 'project1');
                }
                return true;
            }))
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-selected', ['--project' => $projectName])
            ->expectsConfirmation('Should all probes for all projects be executed?', 'yes')
            ->expectsOutput(CliIcon::RUN->label('Running all probes...'))
            ->expectsOutput('Target URL is currently reachable (Response time: 100 ms)')
            ->expectsOutput('Result stored successfully in project project1.')
            ->expectsOutput(CliIcon::OK->label('All probes have been executed.'))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_runs_probes_with_force_option(): void
    {
        $projectName = 'project1';
        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(['project' => $projectName, 'status' => 'enabled']);

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::on(function ($callback) use ($result) {
                if (is_callable($callback)) {
                    $callback($result, 'project1');
                }
                return true;
            }))
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-selected', ['--project' => $projectName, '--force' => true])
            ->expectsOutput(CliIcon::RUN->label('Running all probes...'))
            ->expectsOutput('Target URL is currently reachable (Response time: 100 ms)')
            ->expectsOutput('Result stored successfully in project project1.')
            ->expectsOutput(CliIcon::OK->label('All probes have been executed.'))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_shows_error_when_project_not_found(): void
    {
        $projectName = 'non-existent';

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(null);

        $this->handlerMock->shouldReceive('handle')
            ->never();

        $this->artisan('uplinkr:project:run-selected', ['--project' => $projectName, '--force' => true])
            ->expectsOutput(CliIcon::ERROR->label('Project non-existent not found.'))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_shows_info_when_no_probes_found(): void
    {
        $projectName = 'empty-project';

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(['project' => $projectName, 'status' => 'enabled']);

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::any())
            ->andReturn([]);

        $this->artisan('uplinkr:project:run-selected', ['--project' => $projectName, '--force' => true])
            ->expectsOutput(CliIcon::RUN->label('Running all probes...'))
            ->expectsOutput(CliIcon::WARN->label('No probes found for project empty-project.'))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_fails_when_no_project_option_provided(): void
    {
        $this->artisan('uplinkr:project:run-selected', ['--force' => true])
            ->expectsOutput(CliIcon::ERROR->label('Updating failed. Please check the project name. This is a required field.'))
            ->assertExitCode(CommandAlias::INVALID);
    }

    public function test_it_aborts_on_confirmation_decline(): void
    {
        $projectName = 'project1';
        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(['project' => $projectName, 'status' => 'enabled']);

        $this->handlerMock->shouldNotReceive('handle');

        $this->artisan('uplinkr:project:run-selected', ['--project' => $projectName])
            ->expectsConfirmation('Should all probes for all projects be executed?', 'no')
            ->expectsOutput(CliIcon::WARN->label('The process was aborted.'))
            ->assertExitCode(CommandAlias::INVALID);
    }

    public function test_it_fails_when_project_is_disabled(): void
    {
        $projectName = 'disabled-project';

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(['project' => $projectName, 'status' => 'disabled']);

        $this->handlerMock->shouldNotReceive('handle');

        $this->artisan('uplinkr:project:run-selected', ['--project' => $projectName, '--force' => true])
            ->expectsOutput(CliIcon::WARN->label('Project disabled-project is disabled.'))
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
