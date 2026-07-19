<?php

namespace Tests\Integration\MySql;

use App\Enums\PointLedgerType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\DriverProfile;
use App\Models\PointLedger;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentWithdrawalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Concurrent withdrawal test requires the dedicated MySQL configuration.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_two_parallel_requests_cannot_spend_the_same_available_points(): void
    {
        $user = User::query()->create([
            'name' => 'Concurrent Driver',
            'email' => 'concurrent-driver@example.test',
            'phone' => '081234567890',
            'password' => 'password',
            'role' => UserRole::DRIVER,
            'status' => UserStatus::ACTIVE,
        ]);

        $profile = DriverProfile::query()->create([
            'user_id' => $user->id,
            'available_points' => 100,
            'held_points' => 0,
        ]);

        $barrier = tempnam(sys_get_temp_dir(), 'withdrawal-barrier-');
        $resultOne = tempnam(sys_get_temp_dir(), 'withdrawal-result-');
        $resultTwo = tempnam(sys_get_temp_dir(), 'withdrawal-result-');

        unlink($barrier);

        try {
            $processes = [
                $this->worker($profile->id, 60, $resultOne, $barrier),
                $this->worker($profile->id, 60, $resultTwo, $barrier),
            ];

            foreach ($processes as $process) {
                $process->start();
            }

            usleep(250_000);
            touch($barrier);

            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() ?: $process->getOutput());
            }

            $results = [
                json_decode((string) file_get_contents($resultOne), true, flags: JSON_THROW_ON_ERROR),
                json_decode((string) file_get_contents($resultTwo), true, flags: JSON_THROW_ON_ERROR),
            ];

            $this->assertCount(1, array_filter($results, fn (array $result): bool => $result['success'] === true));
            $this->assertCount(1, array_filter($results, fn (array $result): bool => $result['success'] === false));

            $failed = collect($results)->firstWhere('success', false);
            $this->assertSame(['Saldo poin tersedia tidak mencukupi.'], $failed['errors']['points'] ?? null);

            $profile->refresh();
            $this->assertSame(40, $profile->available_points);
            $this->assertSame(60, $profile->held_points);
            $this->assertSame(1, Withdrawal::query()->count());
            $this->assertSame(1, PointLedger::query()->where('type', PointLedgerType::HOLD)->count());
        } finally {
            @unlink($barrier);
            @unlink($resultOne);
            @unlink($resultTwo);
        }
    }

    private function worker(int $driverProfileId, int $points, string $resultFile, string $barrier): Process
    {
        return new Process([
            PHP_BINARY,
            base_path('artisan'),
            'withdrawal:attempt',
            (string) $driverProfileId,
            (string) $points,
            $resultFile,
            '--barrier='.$barrier,
            '--no-interaction',
        ], base_path(), timeout: 20);
    }
}
