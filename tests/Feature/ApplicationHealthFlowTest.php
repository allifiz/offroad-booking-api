<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationHealthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_health_is_successful_when_core_dependencies_are_available(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');

        $this->artisan('app:health --json')
            ->expectsOutputToContain('"healthy": true')
            ->expectsOutputToContain('"database"')
            ->expectsOutputToContain('"storage"')
            ->expectsOutputToContain('"queue_tables"')
            ->assertSuccessful();
    }
}
