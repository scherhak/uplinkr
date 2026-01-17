<?php

use Illuminate\Notifications\Messages\MailMessage;

return [

    /*
     * Common messages
     */
    'common_process_aborted' => 'The process was aborted.',

    /*
     * Probe parts
     */
    'probe_checking' => 'Should the check of :url be started?',
    'probe_reachable' => 'Target URL is currently reachable (Response time: :time_in_ms ms)',
    'probe_unreachable' => 'Target URL is currently NOT reachable (Status response: :status_header with response time: :time_in_ms ms)',
    'probe_error' => 'An error is occurred.',
    'probe_no_url_provided' => 'No URL provided.',
    'probe_stored' => 'Result stored successfully in project :project.',

    /*
     * Projects parts
     */
    'project_archive_start' => 'Should project :project now be archived?',
    'project_archive_failed' => 'Archiving of Project :project failed.',
    'project_archive_success' => 'Project :project archived successfully.',
    'project_not_found' => 'Project :project not found.',
    'project_archive_option_missing' => 'The archive option seems to be missing.',
    'project_init_start' => 'Should project :project be created and initialized now?',
    'project_init_exists_confirm' => 'Project :project already exists. Should it be re-initialized (metadata will be overwritten, but created_at and probes will be kept)?',
    'project_init_failed' => 'Initialization failed. Please check the project name.',
    'project_init_success' => 'Initialization of project :project successful.',
    'project_update_start' => 'Should project :project be updated now?',
    'project_update_failed' => 'Updating project :project failed. Project might not exist.',
    'project_update_failed_validation' => 'Updating failed. Please check the project name. This is a required field.',
    'project_update_success' => 'Project :project updated successfully.',
    'project_add_probe_start' => 'Should URL :url be added to or updated in project :project now?',
    'project_add_probe_failed' => 'Adding failed. Please check the URL and project name. These are required fields.',
    'project_add_probe_success' => 'Probe for URL :url added or updated to project :project successfully.',
    'project_remove_probe_start' => 'Should URL :url be removed from project :project now?',
    'project_remove_probe_failed' => 'Removal failed. Please check the URL and project name. These are required fields.',
    'project_remove_probe_success' => 'The Probe has been successfully removed from the project.',
    'project_run_probes_confirm' => 'Should all probes for all projects be executed?',
    'project_run_probes_start' => 'Running all probes...',
    'project_run_probes_no_projects' => 'No projects found in :path.',
    'project_run_probes_no_probes' => 'No probes found for project :project.',
    'project_run_probes_running_for_project' => 'Running :count probes for project :project...',
    'project_run_probes_success' => 'All probes have been executed.',
    'project_disable_start' => 'Should project :project be disabled now?',
    'project_disable_failed' => 'Disabling project :project failed. Project might not exist.',
    'project_disable_success' => 'Project :project disabled successfully.',
    'project_disabled' => 'Project :project is disabled.',
    'project_enable_start' => 'Should project :project be enabled now?',
    'project_enable_failed' => 'Enabling project :project failed. Project might not exist.',
    'project_enable_success' => 'Project :project enabled successfully.',
    'project_alerts_start' => 'Should alert settings for project :project be updated now?',
    'project_alerts_failed' => 'Updating alert settings for project :project failed. Project might not exist.',
    'project_alerts_success' => 'Alert settings for project :project updated successfully.',
    'project_alerts_decisions_validation_failed' => 'Validation failed. Please provide a valid project name.',
    'project_alerts_decisions_none_project' => 'No alerts triggered for project ":project".',
    'project_alerts_decisions_none_all' => 'No alerts triggered for any project.',
    'project_alerts_decisions_found_project' => 'Found :count alert decision(s) for project ":project":',
    'project_alerts_decisions_found_all' => 'Found :count alert decision(s) across all projects:',
    'project_alerts_decisions_list_item' => ' - Project: :project | Probe: :probe | Reason: :reason | Count: :count',
    'project_alerts_mail_subject' => ':prefix Alert: Project ":project" - Probe ":probe" failed',
    'project_alerts_mail_greeting' => 'An alert has been triggered for project :project!',
    'project_alerts_mail_details_head' => 'Notification details',
    'project_alerts_mail_details_project' => '- Project: :project',
    'project_alerts_mail_details_probe' => '- Probe: :probe',
    'project_alerts_mail_details_reason' => '- Reason: Multiple consecutive failures, or response times that are too slow.',
    'project_alerts_mail_details_failure_count' => '- Failure count: :failureCount',
    'project_alerts_mail_details_alert_time' => '- Alert time: :alertTime',
    'project_alerts_mail_accompanying_text_head' => 'What this means',
    'project_alerts_mail_accompanying_text' => 'The monitored URL could not be accessed successfully for an extended period of time, or responded too slowly. Please check the status of the server, the network connection, and/or the SSL certificate configuration.',
    'project_alerts_mail_accompanying_text_note' => 'You will automatically receive further notifications in :cooldownMinutes minutes, if the issue persists.',
    'project_alerts_mail_alert_settings_head' => 'Current alert settings',
    'project_alerts_mail_alert_settings_trigger_after_failures' => '- The alarm was triggered after :triggerAfterFailures consecutive errors.',
    'project_alerts_mail_alert_settings_latency_threshold_ms' => 'The maximum response time is currently set to :latencyThresholdMs milliseconds.',

    /*
     * Prune project and probes results
     */
    'prune_start' => 'Starting prune process.',
    'prune_success' => 'Results cleared successfully.',
    'prune_before' => 'Are you sure you want to delete files in project :project before :before?',
    'prune_before_count_deleted_files' => 'Deleted :deletedCount files older than :before',
    'prune_before_no_files_found' => 'No files found older than :before or directory empty.',
    'prune_before_invalid_date_format' => 'Invalid date format for --before. Please use Y-m-d.',
    'prune_project' => 'Are you sure you want to delete the entire Project :project?',
    'prune_project_by_name_success' => 'Project :project has been successfully deleted.',
    'prune_project_folder_does_not_exists' => 'Project folder :project does not exist.',
    'prune_wipe_all_not_allowed' => 'Wipe all is not allowed.',
    'prune_wipe_all' => 'Are you sure you want to prune > ALL < results?',
    'prune_wipe_all_success' => 'All projects and probe data are wiped out successfully.',
    'prune_wipe_all_new_folder_created' => 'New basic folder created for uplinkr',
    'prune_wipe_all_no_files_wiped' => 'Project folder :project is empty.',
];