<?php

namespace App\Console\Commands;

use App\Models\DriverProfile;
use App\Services\WithdrawalService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttemptWithdrawal extends Command
{
    protected $signature = 'withdrawal:attempt
        {driver_profile_id}
        {points}
        {result_file}
        {--barrier= : Optional barrier file that must exist before processing}';

    protected $description = 'Internal worker used by the MySQL withdrawal concurrency integration test.';

    public function handle(WithdrawalService $withdrawalService): int
    {
        $barrier = $this->option('barrier');
        $deadline = microtime(true) + 10;

        while ($barrier && ! file_exists($barrier) && microtime(true) < $deadline) {
            usleep(20_000);
        }

        try {
            $profile = DriverProfile::query()->findOrFail((int) $this->argument('driver_profile_id'));
            $withdrawal = $withdrawalService->request($profile, [
                'points' => (int) $this->argument('points'),
                'bank_name' => 'Concurrency Bank',
                'account_number' => '000111222',
                'account_name' => 'Concurrency Driver',
            ]);

            $result = ['success' => true, 'withdrawal_id' => $withdrawal->id];
        } catch (ValidationException $exception) {
            $result = ['success' => false, 'errors' => $exception->errors()];
        } catch (Throwable $exception) {
            $result = ['success' => false, 'exception' => $exception::class, 'message' => $exception->getMessage()];
        }

        file_put_contents((string) $this->argument('result_file'), json_encode($result, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
