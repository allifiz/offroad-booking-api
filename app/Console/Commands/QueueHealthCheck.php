<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueHealthCheck extends Command
{
    protected $signature = 'queue:health {--json : Output JSON only}';

    protected $description = 'Check pending, stale, and failed database queue jobs';

    public function handle(): int
    {
        if (! Schema::hasTable('jobs') || ! Schema::hasTable('failed_jobs')) {
            return $this->respond([
                'healthy' => false,
                'message' => 'Queue tables are missing. Run php artisan migrate.',
            ], self::FAILURE);
        }

        $now = now()->timestamp;
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $oldestAvailableAt = DB::table('jobs')->min('available_at');
        $oldestJobSeconds = $oldestAvailableAt === null
            ? 0
            : max(0, $now - (int) $oldestAvailableAt);

        $thresholds = [
            'pending_jobs' => (int) config('queue_health.pending_jobs_warning', 100),
            'oldest_job_seconds' => (int) config('queue_health.oldest_job_seconds_warning', 300),
            'failed_jobs' => (int) config('queue_health.failed_jobs_warning', 1),
        ];

        $healthy = $pendingJobs < $thresholds['pending_jobs']
            && $oldestJobSeconds < $thresholds['oldest_job_seconds']
            && $failedJobs < $thresholds['failed_jobs'];

        return $this->respond([
            'healthy' => $healthy,
            'connection' => config('queue.default'),
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'oldest_job_seconds' => $oldestJobSeconds,
            'thresholds' => $thresholds,
            'checked_at' => now()->toISOString(),
        ], $healthy ? self::SUCCESS : self::FAILURE);
    }

    private function respond(array $payload, int $exitCode): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));

            return $exitCode;
        }

        $this->components->info($payload['healthy'] ? 'Queue is healthy.' : 'Queue requires attention.');

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            $this->line(sprintf('%s: %s', $key, var_export($value, true)));
        }

        return $exitCode;
    }
}
