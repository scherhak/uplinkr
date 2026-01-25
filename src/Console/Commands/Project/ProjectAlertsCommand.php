<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\Alerts\AlertHandler;
use Uplinkr\Support\CliIcon;

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
                            {--failures= : Trigger after X failures}
                            {--cooldown= : Cooldown in minutes}
                            {--threshold= : Latency threshold in ms}
                            {--slow= : Trigger after X slow responses}
                            {--channels= : Comma separated list of channels}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates alert settings for a project.';

    /**
     * Execute the console command.
     *
     * @param AlertHandler $alertHandler
     * @return int
     * @throws JsonException
     */
    public function handle(AlertHandler $alertHandler): int
    {
        $project = $this->option('project');
        $enabled = filter_var($this->option('enabled'), FILTER_VALIDATE_BOOLEAN);
        $failures = $this->option('failures') ? (int)$this->option('failures') : null;
        $cooldown = $this->option('cooldown') ? (int)$this->option('cooldown') : null;
        $threshold = $this->option('threshold') ? (int)$this->option('threshold') : null;
        $slow = $this->option('slow') ? (int)$this->option('slow') : null;
        $channels = $this->option('channels') ? explode(',', $this->option('channels')) : null;
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
                'failures' => 'nullable|integer|min:1',
                'cooldown' => 'nullable|integer|min:1',
                'threshold' => 'nullable|integer|min:1',
                'slow' => 'nullable|integer|min:1',
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
                    $this->info(CliIcon::OK->label(text: __('uplinkr::messages.project_alerts_success', ['project' => $project])));

                    return CommandAlias::SUCCESS;
                }

                $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_alerts_failed', ['project' => $project])));

                return CommandAlias::FAILURE;
            }

            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.common_process_aborted')));

            return CommandAlias::INVALID;

        }

        $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_alerts_validation_failed')));

        return CommandAlias::INVALID;
    }
}
