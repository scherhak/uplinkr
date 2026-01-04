<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ProbeSelectedProjectsHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Support\Logger;
use Uplinkr\Traits\HandlesProbeOutput;

/**
 * Class ProjectRunSelectedProbeCommand
 * @package Uplinkr\Console\Commands\Project
 *
 * This class is responsible for handling the execution of the `uplinkr:project:run-selected-probe` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectRunSelectedProbeCommand extends Command
{
    use HandlesProbeOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:run-selected-probe 
                            {--project= : Name of the project to run probes for}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all probes for a selected project defined in settings.json';

    /**
     * Handles the execution of the command.
     *
     * @param ProbeSelectedProjectsHandler $handler
     * @param UplinkrConfig $config
     * @param ProjectStorageInterface $projectStorage
     * @return int
     * @throws \JsonException
     */
    public function handle(ProbeSelectedProjectsHandler $handler, UplinkrConfig $config, ProjectStorageInterface $projectStorage): int
    {
        $projectName = $this->option('project');
        $force = $this->option('force');

        if (empty($projectName)) {
            $this->error(__('uplinkr::messages.project_update_failed_validation'));

            return CommandAlias::INVALID;
        }

        $project = $projectStorage->findProject($projectName);
        if ($project === null) {
            $message = __('uplinkr::messages.project_not_found', ['project' => $projectName]);
            $this->error($message);
            Logger::get()->warning($message);

            return CommandAlias::SUCCESS;
        }

        $projectValues = new ProjectValues($project);
        if ($projectValues->getStatus() === 'disabled') {
            $this->warn(__('uplinkr::messages.project_disabled', ['project' => $projectName]));

            return CommandAlias::SUCCESS;
        }

        if (!$force && !$this->confirm(__('uplinkr::messages.project_run_probes_confirm', ['count' => $projectName]))) {
            $this->warn(__('uplinkr::messages.common_process_aborted'));

            return CommandAlias::INVALID;
        }

        $this->info(__('uplinkr::messages.project_run_probes_start'));

        $results = $handler->handle($projectName, function ($result, $project) use ($config) {
            $this->resultMessages(result: $result, project: $project, config: $config);
        });

        if ($results === null) {
            $message = __('uplinkr::messages.project_not_found', ['project' => $projectName]);
            $this->error($message);
            \Log::warning('Uplinkr: ' . $message);
            return CommandAlias::SUCCESS;
        }

        if (empty($results)) {
            $this->line(__('uplinkr::messages.project_run_probes_no_probes', ['project' => $projectName]));
        } else {
            $this->info(__('uplinkr::messages.project_run_probes_success'));
        }

        return CommandAlias::SUCCESS;
    }
}
