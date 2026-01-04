<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ProbeAllProjectsHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Support\Logger;
use Uplinkr\Traits\HandlesProbeOutput;

/**
 * Class ProjectRunProbesCommand
 * @package Uplinkr\Console\Commands\Project
 *
 * This class is responsible for handling the execution of the `uplinkr:project:run-probes` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectRunProbesCommand extends Command
{
    use HandlesProbeOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:run-probes 
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all probes for all projects defined in settings.json';

    /**
     * Handles the execution of the command.
     *
     * @param ProbeAllProjectsHandler $handler
     * @param UplinkrConfig $config
     * @param ProjectStorageInterface $projectStorage
     * @return int
     */
    public function handle(ProbeAllProjectsHandler $handler, UplinkrConfig $config, ProjectStorageInterface $projectStorage): int
    {
        $force = $this->option('force');

        if (!$force && !$this->confirm(__('uplinkr::messages.project_run_probes_confirm', ['count' => 'all']))) {
            $this->warn(__('uplinkr::messages.common_process_aborted'));

            return CommandAlias::INVALID;
        }

        $this->info(__('uplinkr::messages.project_run_probes_start'));

        $projects = $projectStorage->allProjects();

        if (empty($projects)) {
            $this->warn(__('uplinkr::messages.project_run_probes_no_projects', ['path' => $projectStorage->getStoragePath()]));

            return CommandAlias::SUCCESS;
        }

        foreach ($projects as $key => $project) {
            if ($project === null) {
                $projectName = basename($projectStorage->allProjectDirectories()[$key]);
                $message = __('uplinkr::messages.project_not_found', ['project' => $projectName]);
                $this->error($message);
                Logger::log()->warning($message);

                continue;
            }

            $projectValues = new ProjectValues($project);
            $projectName = $projectValues->getName();
            $probes = $projectValues->getProbes();

            if ($projectValues->getStatus() === 'disabled') {
                $this->warn(__('uplinkr::messages.project_disabled', ['project' => $projectName]));
                continue;
            }

            if (empty($probes)) {
                $this->line(__('uplinkr::messages.project_run_probes_no_probes', ['project' => $projectName]));
                continue;
            }

            $this->info(__('uplinkr::messages.project_run_probes_running_for_project', [
                'count' => count($probes),
                'project' => $projectName
            ]));

            $handler->handleProject($project, function ($result, $project) use ($config) {
                $this->resultMessages(result: $result, project: $project, config: $config);
            });
        }

        $this->info(__('uplinkr::messages.project_run_probes_success'));

        return CommandAlias::SUCCESS;
    }
}
