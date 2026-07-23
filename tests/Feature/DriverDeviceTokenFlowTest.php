<?php

namespace Tests\Feature;

use App\Models\DriverDeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverDeviceTokenFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_register_and_refresh_device_token(): void
    {
        $driver = User::factory()->driver()->create();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/driver/device-tokens', [
            'token' => 'fcm-token-1',
            'platform' => 'android',
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/v1/driver/device-tokens', [
            'token' => 'fcm-token-1',
            'platform' => 'android',
        ])->assertOk();

        $this->assertDatabaseCount('driver_device_tokens', 1);
        $this->assertDatabaseHas('driver_device_tokens', [
            'user_id' => $driver->id,
            'token_hash' => hash('sha256', 'fcm-token-1'),
            'platform' => 'android',
        ]);
    }

    public function test_driver_can_remove_only_their_own_device_token(): void
    {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $driver->id,
            'token' => 'driver-token',
            'token_hash' => hash('sha256', 'driver-token'),
            'platform' => 'android',
        ]);
        DriverDeviceToken::query()->create([
            'user_id' => $otherDriver->id,
            'token' => 'other-token',
            'token_hash' => hash('sha256', 'other-token'),
            'platform' => 'android',
        ]);

        Sanctum::actingAs($driver);

        $this->deleteJson('/api/v1/driver/device-tokens', [
            'token' => 'driver-token',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('driver_device_tokens', [
            'token_hash' => hash('sha256', 'driver-token'),
        ]);
        $this->assertDatabaseHas('driver_device_tokens', [
            'token_hash' => hash('sha256', 'other-token'),
        ]);
    }
}
