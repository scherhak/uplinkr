<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\Alerts\AlertDecisionHandler;
use Uplinkr\Support\Logger;

/**
 * Class ProjectAlertDecisionCommand
 * @package Uplinkr\Console\Commands\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectAlertDecisionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:alert:decision 
                            {--project= : Name of the project to check for alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decides whether an alert should be triggered for a project.';

    /**
     * @param AlertDecisionHandler $alertDecisionHandler
     * @return int
     * @throws JsonException
     */
    public function handle(AlertDecisionHandler $alertDecisionHandler): int
    {
        $project = $this->option('project');

        if ($project !== null) {
            $validate = Validator::make(
                ['project' => $project],
                ['project' => 'string']
            );

            if ($validate->fails()) {
                $message = 'Validation failed. Please provide a valid project name.';
                $this->error($message);
                Logger::log()->warning($message);

                return CommandAlias::SUCCESS;
            }
        }

        $decisions = $alertDecisionHandler->handle($project);

        if (empty($decisions)) {
            $message = $project 
                ? sprintf('No alerts triggered for project "%s".', $project)
                : 'No alerts triggered for any project.';
            $this->info($message);

            return CommandAlias::SUCCESS;
        }

        if ($project) {
            $this->info(sprintf('Found %d alert decision(s) for project "%s":', count($decisions), $project));
        } else {
            $this->info(sprintf('Found %d alert decision(s) across all projects:', count($decisions)));
        }

        foreach ($decisions as $decision) {
            $this->line(sprintf(
                ' - Project: <fg=magenta>%s</> | Probe: <fg=cyan>%s</> | Reason: <fg=yellow>%s</> | Count: <fg=red>%d</>',
                $decision['project'],
                $decision['probe'],
                $decision['reason'],
                $decision['count']
            ));
        }

        return CommandAlias::SUCCESS;
    }
}
