<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationHealthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_health_is_successful_when_core_dependencies_are_available(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');

        $exitCode = Artisan::call('app:health', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($payload['healthy']);
        $this->assertTrue($payload['checks']['database']['healthy']);
        $this->assertTrue($payload['checks']['storage']['healthy']);
        $this->assertTrue($payload['checks']['queue_tables']['healthy']);
        $this->assertArrayHasKey('checked_at', $payload);
    }
}
