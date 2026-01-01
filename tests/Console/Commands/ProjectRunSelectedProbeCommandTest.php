<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ProbeSelectedProjectsHandler;
use Uplinkr\Tests\TestCase;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProjectRunSelectedProbeCommandTest extends TestCase
{
    private MockInterface|ProbeSelectedProjectsHandler $handlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handlerMock = Mockery::mock(ProbeSelectedProjectsHandler::class);
        $this->app->instance(ProbeSelectedProjectsHandler::class, $this->handlerMock);
    }

    public function test_it_runs_probes_for_selected_project_with_confirmation(): void
    {
        $projectName = 'project1';
        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::on(function($callback) use ($result) {
                if (is_callable($callback)) {
                    $callback($result, 'project1');
                }
                return true;
            }))
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-selected-probe', ['--project' => $projectName])
            ->expectsConfirmation('Should all probes for all projects be executed?', 'yes')
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Target URL is currently reachable (Response time: 100 ms)')
            ->expectsOutput('Result stored successfully in project project1.')
            ->expectsOutput('All probes have been executed.')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_runs_probes_with_force_option(): void
    {
        $projectName = 'project1';
        $result = ['probe_status' => 'reachable', 'probe_message' => ['duration_ms' => 100]];

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::on(function($callback) use ($result) {
                if (is_callable($callback)) {
                    $callback($result, 'project1');
                }
                return true;
            }))
            ->andReturn([$result]);

        $this->artisan('uplinkr:project:run-selected-probe', ['--project' => $projectName, '--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Target URL is currently reachable (Response time: 100 ms)')
            ->expectsOutput('Result stored successfully in project project1.')
            ->expectsOutput('All probes have been executed.')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_shows_error_when_project_not_found(): void
    {
        $projectName = 'non-existent';

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::any())
            ->andReturn(null);

        $this->artisan('uplinkr:project:run-selected-probe', ['--project' => $projectName, '--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('Project non-existent not found.')
            ->assertExitCode(CommandAlias::FAILURE);
    }

    public function test_it_shows_info_when_no_probes_found(): void
    {
        $projectName = 'empty-project';

        $this->handlerMock->shouldReceive('handle')
            ->once()
            ->with($projectName, Mockery::any())
            ->andReturn([]);

        $this->artisan('uplinkr:project:run-selected-probe', ['--project' => $projectName, '--force' => true])
            ->expectsOutput('Running all probes...')
            ->expectsOutput('No probes found for project empty-project.')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_fails_when_no_project_option_provided(): void
    {
        $this->artisan('uplinkr:project:run-selected-probe', ['--force' => true])
            ->expectsOutput('Updating failed. Please check the project name. This is a required field.')
            ->assertExitCode(CommandAlias::INVALID);
    }

    public function test_it_aborts_on_confirmation_decline(): void
    {
        $this->handlerMock->shouldNotReceive('handle');

        $this->artisan('uplinkr:project:run-selected-probe', ['--project' => 'project1'])
            ->expectsConfirmation('Should all probes for all projects be executed?', 'no')
            ->expectsOutput('The process was aborted.')
            ->assertExitCode(CommandAlias::INVALID);
    }
}
