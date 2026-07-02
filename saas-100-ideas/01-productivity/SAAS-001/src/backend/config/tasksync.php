<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | TaskSync Pro Application Settings
    |--------------------------------------------------------------------------
    |
    | Core configuration values for the TaskSync Pro application.
    |
    */

    /*
     * Default pagination size for list endpoints.
     */
    'pagination_size' => env('TASKSYNC_PAGINATION_SIZE', 15),

    /*
     * Maximum pagination size allowed per request.
     */
    'max_pagination_size' => env('TASKSYNC_MAX_PAGINATION_SIZE', 100),

    /*
     * Default workspace limits for free plan.
     */
    'workspace' => [
        'default_plan' => env('TASKSYNC_DEFAULT_PLAN', 'free'),
        'max_members_free' => env('TASKSYNC_MAX_MEMBERS_FREE', 3),
        'max_members_pro' => env('TASKSYNC_MAX_MEMBERS_PRO', 25),
        'max_members_business' => env('TASKSYNC_MAX_MEMBERS_BUSINESS', 100),
        'max_projects_free' => env('TASKSYNC_MAX_PROJECTS_FREE', 5),
        'max_projects_pro' => env('TASKSYNC_MAX_PROJECTS_PRO', 50),
        'max_projects_business' => env('TASKSYNC_MAX_PROJECTS_BUSINESS', 500),
    ],

    /*
     * Default user preferences.
     */
    'user' => [
        'default_locale' => env('TASKSYNC_DEFAULT_LOCALE', 'ar'),
        'default_timezone' => env('TASKSYNC_DEFAULT_TIMEZONE', 'UTC'),
        'supported_locales' => ['ar', 'en'],
    ],

    /*
     * Timer settings.
     */
    'timer' => [
        'max_daily_minutes' => env('TASKSYNC_MAX_DAILY_MINUTES', 1440),
        'idle_timeout_minutes' => env('TASKSYNC_TIMER_IDLE_TIMEOUT', 480),
    ],

    /*
     * File upload limits.
     */
    'uploads' => [
        'max_file_size' => env('TASKSYNC_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_mime_types' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
    ],

    /*
     * Invitation defaults.
     */
    'invitation' => [
        'expires_days' => env('TASKSYNC_INVITE_EXPIRY_DAYS', 7),
        'max_per_hour' => env('TASKSYNC_MAX_INVITES_PER_HOUR', 20),
    ],

    /*
     * Webhook settings.
     */
    'webhook' => [
        'timeout_seconds' => env('TASKSYNC_WEBHOOK_TIMEOUT', 10),
        'max_retries' => env('TASKSYNC_WEBHOOK_MAX_RETRIES', 5),
        'retry_delays' => [0, 10, 60, 300, 3600],
    ],
];
