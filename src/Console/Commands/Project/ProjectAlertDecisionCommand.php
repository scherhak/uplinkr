<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\Alerts\AlertDecisionHandler;
use Uplinkr\Support\CliIcon;
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
                $message = __('uplinkr::messages.project_alerts_decisions_validation_failed');
                $this->error(CliIcon::ERROR->label(text: $message));
                Logger::log()->warning($message);

                return CommandAlias::SUCCESS;
            }
        }

        $decisions = $alertDecisionHandler->handle($project);

        if (empty($decisions)) {
            $message = $project
                ? __('uplinkr::messages.project_alerts_decisions_none_project', ['project' => $project])
                : __('uplinkr::messages.project_alerts_decisions_none_all');
            $this->info(CliIcon::ERROR->label(text: $message));
            Logger::log()->warning($message);

            return CommandAlias::SUCCESS;
        }

        if ($project) {
            $this->info(__('uplinkr::messages.project_alerts_decisions_found_project', [
                'count' => count($decisions),
                'project' => $project
            ]));
        } else {
            $this->info(__('uplinkr::messages.project_alerts_decisions_found_all', [
                'count' => count($decisions)
            ]));
        }

        foreach ($decisions as $decision) {
            $this->line(__('uplinkr::messages.project_alerts_decisions_list_item', [
                'project' => sprintf('<fg=magenta>%s</>', $decision['project']),
                'probe' => sprintf('<fg=cyan>%s</>', $decision['probe']),
                'reason' => sprintf('<fg=yellow>%s</>', $decision['reason']),
                'count' => sprintf('<fg=red>%d</>', $decision['count'])
            ]));
        }

        return CommandAlias::SUCCESS;
    }
}
