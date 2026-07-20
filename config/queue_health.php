<?php

return [
    'pending_jobs_warning' => (int) env('QUEUE_HEALTH_PENDING_WARNING', 100),
    'oldest_job_seconds_warning' => (int) env('QUEUE_HEALTH_OLDEST_SECONDS_WARNING', 300),
    'failed_jobs_warning' => (int) env('QUEUE_HEALTH_FAILED_WARNING', 1),
];
