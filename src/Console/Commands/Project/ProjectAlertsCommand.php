<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\AlertHandler;

/**
 * Class ProjectAlertsCommand
 * @package Uplinkr\Console\Commands\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:alerts 
                            {--project= : Name of the project to update alert settings}
                            {--enabled=true : Enable or disable alerts (true/false)}
                            {--failures=3 : Trigger after X failures}
                            {--cooldown=30 : Cooldown in minutes}
                            {--threshold=1500 : Latency threshold in ms}
                            {--slow=3 : Trigger after X slow responses}
                            {--channels=mail,slack : Comma separated list of channels}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates alert settings for a project.';

    /**
     * @throws JsonException
     */
    public function handle(AlertHandler $alertHandler): int
    {
        $project = $this->option('project');
        $enabled = filter_var($this->option('enabled'), FILTER_VALIDATE_BOOLEAN);
        $failures = (int) $this->option('failures');
        $cooldown = (int) $this->option('cooldown');
        $threshold = (int) $this->option('threshold');
        $slow = (int) $this->option('slow');
        $channels = explode(',', $this->option('channels'));
        $force = $this->option('force');

        $validate = Validator::make(
            [
                'project' => $project,
                'failures' => $failures,
                'cooldown' => $cooldown,
                'threshold' => $threshold,
                'slow' => $slow,
            ],
            [
                'project' => 'required|string',
                'failures' => 'required|integer|min:1',
                'cooldown' => 'required|integer|min:1',
                'threshold' => 'required|integer|min:1',
                'slow' => 'required|integer|min:1',
            ],
        );

        if ($validate->passes()) {

            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.project_alerts_start',
                    [
                        'project' => $project
                    ]
                ));
            }

            if ($execute) {
                $success = $alertHandler->handle(options: [
                    'project' => $project,
                    'enabled' => $enabled,
                    'trigger_after_failures' => $failures,
                    'cooldown_minutes' => $cooldown,
                    'latency_threshold_ms' => $threshold,
                    'trigger_after_slow' => $slow,
                    'channels' => $channels,
                ]);

                if ($success) {
                    $this->info(__('uplinkr::messages.project_alerts_success', ['project' => $project]));

                    return CommandAlias::SUCCESS;
                }

                $this->error(__('uplinkr::messages.project_alerts_failed', ['project' => $project]));

                return CommandAlias::FAILURE;
            }

            $this->warn(__('uplinkr::messages.common_process_aborted'));

            return CommandAlias::INVALID;

        }

        $this->error('Validation failed. Please check your inputs.');

        return CommandAlias::INVALID;
    }
}
