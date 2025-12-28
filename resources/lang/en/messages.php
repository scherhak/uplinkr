<?php

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
    'project_init_failed' => 'Initialization failed. Please check the project name.',
    'project_init_success' => 'Initialization of project :project successful.',
    'project_add_probe_start' => 'Should URL :url be added to or updated in project :project now?',
    'project_add_probe_failed' => 'Adding failed. Please check the URL and project name. These are required fields.',
    'project_add_probe_success' => 'Probe for URL :url added or updated to project :project successfully.',
    'project_remove_probe_start' => 'Should URL :url be removed from project :project now?',
    'project_remove_probe_failed' => 'Removal failed. Please check the URL and project name. These are required fields.',
    'project_remove_probe_success' => 'The Probe has been successfully removed from the project.',

    /*
     * Prune project and probes results
     */
    'prune_start' => 'Starting prune process.',
    'prune_success' => 'Results cleared successfully.',
    'prune_before' => 'Are you sure you want to delete files in project :project before :before?',
    'prune_before_count_deleted_files' => 'Deleted :deletedCount files older than :before',
    'prune_before_no_files_found' => 'No files found older than :before or directory empty.',
    'prune_before_invalid_date_format' => 'Invalid date format for --before. Please use Y-m-d.',
    'prune_project_by_name' => 'Are you sure you want to prune results for project :project?',
    'prune_project_folder_does_not_exists' => 'Project folder :project does not exist.',
    'prune_wipe_all_not_allowed' => 'Wipe all is not allowed.',
    'prune_wipe_all' => 'Are you sure you want to prune > ALL < results?',
    'prune_wipe_all_success' => 'All projects and probe data are wiped out successfully.',
    'prune_wipe_all_new_folder_created' => 'New basic folder created for uplinkr',
    'prune_wipe_all_no_files_wiped' => 'Project folder :project is empty.',
];