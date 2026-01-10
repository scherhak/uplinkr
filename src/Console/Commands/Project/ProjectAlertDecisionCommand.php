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

        $validate = Validator::make(
            ['project' => $project],
            ['project' => 'required|string']
        );

        if ($validate->fails()) {
            $message = 'Validation failed. Please provide a project name.';
            $this->error($message);
            Logger::log()->warning($message);

            return CommandAlias::SUCCESS;
        }

        $decisions = $alertDecisionHandler->handle($project);

        if (empty($decisions)) {
            $this->info(sprintf('No alerts triggered for project "%s".', $project));

            return CommandAlias::SUCCESS;
        }

        $this->info(sprintf('Found %d alert decision(s) for project "%s":', count($decisions), $project));

        foreach ($decisions as $decision) {
            $this->line(sprintf(
                ' - Probe: <fg=cyan>%s</> | Reason: <fg=yellow>%s</> | Count: <fg=red>%d</>',
                $decision['probe'],
                $decision['reason'],
                $decision['count']
            ));
        }

        return CommandAlias::SUCCESS;
    }
}
