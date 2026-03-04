<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ListHandler;

/**
 * Class ProjectListCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:list {--project= : Name of the project to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all available projects with settings, probes and state summary';

    /**
     * Execute the console command.
     *
     * @param ListHandler $listHandler
     * @return int
     */
    public function handle(ListHandler $listHandler): int
    {
        $allProjects = $listHandler->allWithDetails();
        $projects = $allProjects;
        $selectedProject = $this->option('project');

        if (is_string($selectedProject) && trim($selectedProject) !== '') {
            $projects = array_values(array_filter($projects, static function (array $project) use ($selectedProject): bool {
                return Arr::get($project, 'project') === $selectedProject;
            }));

            if (empty($projects)) {
                $this->error(__('uplinkr::messages.project_list_not_found', ['project' => $selectedProject]));
                $this->line(__('uplinkr::messages.project_list_available_projects'));

                foreach ($allProjects as $availableProject) {
                    $this->line(__('uplinkr::messages.project_list_available_project_item', [
                        'project' => Arr::get($availableProject, 'project', 'unknown')
                    ]));
                }

                return CommandAlias::FAILURE;
            }
        }

        foreach ($projects as $project) {
            $status = (string) Arr::get($project, 'status', 'enabled');
            $isEnabled = strtolower($status) === 'enabled';
            $statusColor = $isEnabled ? 'green' : 'red';

            $this->line(sprintf(
                '<fg=%s>%s</>',
                $statusColor,
                __('uplinkr::messages.project_list_header', [
                    'project' => Arr::get($project, 'project', 'unknown'),
                    'label' => Arr::get($project, 'label', '-'),
                    'status' => $status,
                ])
            ));

            $description = Arr::get($project, 'description');
            if (is_string($description) && trim($description) !== '') {
                $this->line(__('uplinkr::messages.project_list_description', ['description' => $description]));
            }

            $this->line(__('uplinkr::messages.project_list_alerts'));
            $this->table(
                ['Enabled', 'Trigger After Failures', 'Cooldown Minutes', 'Latency Threshold (ms)', 'Trigger After Slow', 'Channels'],
                $this->formatAlertRows(Arr::get($project, 'alerts', []))
            );

            $this->table(
                ['Method', 'URL'],
                $this->formatProbeRows(Arr::get($project, 'probes', []))
            );

            $this->line(__('uplinkr::messages.project_list_state'));
            $this->table(
                ['Total Failures', 'Last Notification'],
                [[
                    (int) Arr::get($project, 'state.total_failures', 0),
                    Arr::get($project, 'state.last_notification_at', '-') ?? '-',
                ]]
            );

            $this->newLine();
        }

        return CommandAlias::SUCCESS;
    }

    /**
     * @param array $alerts
     * @return string
     */
    private function formatAlertRows(array $alerts): array
    {
        if (empty($alerts)) {
            return [['false', 0, 0, 0, 0, '-']];
        }

        $rows = [];
        foreach ($alerts as $alert) {
            if (!is_array($alert)) {
                continue;
            }

            $channels = Arr::get($alert, 'channels', []);
            $rows[] = [
                Arr::get($alert, 'enabled', false) ? 'true' : 'false',
                (int) Arr::get($alert, 'trigger_after_failures', 0),
                (int) Arr::get($alert, 'cooldown_minutes', 0),
                (int) Arr::get($alert, 'latency_threshold_ms', 0),
                (int) Arr::get($alert, 'trigger_after_slow', 0),
                is_array($channels) && !empty($channels) ? implode(',', $channels) : '-',
            ];
        }

        return empty($rows) ? [['false', 0, 0, 0, 0, '-']] : $rows;
    }

    /**
     * @param array $probes
     * @return array
     */
    private function formatProbeRows(array $probes): array
    {
        if (empty($probes)) {
            return [['-', '-']];
        }

        $rows = [];
        foreach ($probes as $probe) {
            if (!is_array($probe)) {
                continue;
            }

            $rows[] = [
                Arr::get($probe, 'method', 'GET'),
                Arr::get($probe, 'url', '-'),
            ];
        }

        return empty($rows) ? [['-', '-']] : $rows;
    }
}
