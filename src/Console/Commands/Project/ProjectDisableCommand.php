<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\DisableHandler;

/**
 * Class ProjectDisableCommand
 * @package Uplinkr\Console\Commands\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectDisableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:disable
                            {--project= : Name of the project to disable}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disables a project by setting its status to disabled.';

    /**
     * Execute the console command.
     *
     * @param DisableHandler $disableHandler
     * @return int
     * @throws \JsonException
     */
    public function handle(DisableHandler $disableHandler): int
    {
        $project = $this->option('project');
        $force = $this->option('force');

        $validate = Validator::make(
            ['project' => $project],
            ['project' => 'required|string']
        );

        if ($validate->fails()) {
            if (!$force) {
                $this->error(__('uplinkr::messages.project_disable_failed', ['project' => $project ?? 'unknown']));
            }
            return CommandAlias::INVALID;
        }

        if ($force || $this->confirm(__('uplinkr::messages.project_disable_start', ['project' => $project]))) {
            if ($disableHandler->handle($project)) {
                if (!$force) {
                    $this->info(__('uplinkr::messages.project_disable_success', ['project' => $project]));
                }
                return CommandAlias::SUCCESS;
            }

            if (!$force) {
                $this->error(__('uplinkr::messages.project_disable_failed', ['project' => $project]));
            }
            return CommandAlias::FAILURE;
        }

        if (!$force) {
            $this->warn(__('uplinkr::messages.common_process_aborted'));
        }

        return CommandAlias::INVALID;
    }
}
