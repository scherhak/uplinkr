<?php

return [

    /*
     * Common messages
     */
    'no_url_provided' => 'No URL provided.',


    /*
     * URL probe parts
     */
    'url_checking' => 'Should the check of :url be started?',
    'url_reachable' => 'Target URL is currently reachable (Response time: :time_in_ms ms)',
    'url_unreachable' => 'Target URL is currently NOT reachable (Status response: :status_header with response time: :time_in_ms ms)',
    'url_error' => 'An error is occurred.',
    'url_stored' => 'Result stored successfully in project :project.',


    /*
     * URL probe parts
     */
    'project_archive_start' => 'Should project :project now be archived?',
    'project_archive_failed' => 'Archiving of Project :project failed.',
    'project_archive_success' => 'Project :project archived successfully.',
    'project_not_found' => 'Project :project not found.',
    'project_archive_option_missing' => 'The archive option seems to be missing.',

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