<?php

namespace Tests\Feature;

use App\Notifications\OperationalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueHealthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_notification_has_production_retry_policy(): void
    {
        $notification = new OperationalNotification(
            event: 'booking.updated',
            title: 'Booking diperbarui',
            message: 'Status booking berubah.',
        );

        $this->assertSame(5, $notification->tries);
        $this->assertSame(30, $notification->timeout);
        $this->assertTrue($notification->failOnTimeout);
        $this->assertSame([10, 60, 300, 900], $notification->backoff());
        $this->assertSame('notifications', $notification->queue);
    }

    public function test_queue_health_succeeds_when_queue_is_empty(): void
    {
        $this->artisan('queue:health --json')
            ->expectsOutputToContain('"healthy":true')
            ->assertSuccessful();
    }

    public function test_queue_health_fails_when_failed_job_threshold_is_reached(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => fake()->uuid(),
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => '{}',
            'exception' => 'Synthetic failure',
            'failed_at' => now(),
        ]);

        $this->artisan('queue:health --json')
            ->expectsOutputToContain('"healthy":false')
            ->assertFailed();
    }
}
