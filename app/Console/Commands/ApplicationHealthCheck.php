<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ApplicationHealthCheck extends Command
{
    protected $signature = 'app:health {--json : Return machine-readable JSON}';

    protected $description = 'Check database, storage, queue tables, and application readiness.';

    public function handle(): int
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'queue_tables' => $this->checkQueueTables(),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['healthy']);

        $payload = [
            'healthy' => $healthy,
            'environment' => app()->environment(),
            'checks' => $checks,
            'checked_at' => now()->toISOString(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->components->info($healthy ? 'Application is healthy.' : 'Application is unhealthy.');

            foreach ($checks as $name => $check) {
                $this->line(sprintf('[%s] %s: %s', $check['healthy'] ? 'OK' : 'FAIL', $name, $check['message']));
            }
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('select 1');

            return ['healthy' => true, 'message' => 'Database connection is available.'];
        } catch (Throwable $exception) {
            return ['healthy' => false, 'message' => $exception->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = 'health/.probe';
            Storage::disk(config('filesystems.default'))->put($path, now()->toISOString());
            Storage::disk(config('filesystems.default'))->delete($path);

            return ['healthy' => true, 'message' => 'Default storage is writable.'];
        } catch (Throwable $exception) {
            return ['healthy' => false, 'message' => $exception->getMessage()];
        }
    }

    private function checkQueueTables(): array
    {
        try {
            foreach (['jobs', 'failed_jobs'] as $table) {
                DB::table($table)->limit(1)->count();
            }

            return ['healthy' => true, 'message' => 'Queue tables are accessible.'];
        } catch (Throwable $exception) {
            return ['healthy' => false, 'message' => $exception->getMessage()];
        }
    }
}
