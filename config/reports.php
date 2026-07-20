<?php

return [
    'saved_view_share_activity_retention' => [
        'enabled' => env(
            'REPORT_SAVED_VIEW_SHARE_ACTIVITY_RETENTION_ENABLED',
            false
        ),

        'days' => env(
            'REPORT_SAVED_VIEW_SHARE_ACTIVITY_RETENTION_DAYS'
        ),

        'chunk_size' => env(
            'REPORT_SAVED_VIEW_SHARE_ACTIVITY_RETENTION_CHUNK_SIZE',
            500
        ),

        'schedule' => env(
            'REPORT_SAVED_VIEW_SHARE_ACTIVITY_RETENTION_SCHEDULE',
            'daily'
        ),
    ],
];
