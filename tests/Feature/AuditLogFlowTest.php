<?php

namespace Tests\Feature;

use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_model_changes_create_audit_logs_with_actor_and_request_context(): void
    {
        $driver = User::factory()->driver()->create();
        $profile = $driver->driverProfile()->create([
            'status' => 'available',
            'verification_status' => 'approved',
        ]);

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/v1/driver/vehicles', [
            'name' => 'Audit Jeep',
            'plate_number' => 'AUD 1001',
            'brand' => 'Jeep',
            'model' => 'CJ7',
            'year' => 2020,
            'capacity' => 4,
        ]);

        $response->assertCreated();
        $vehicleId = $response->json('data.id');

        $log = AuditLog::query()
            ->where('event', 'created')
            ->where('subject_type', Vehicle::class)
            ->where('subject_id', $vehicleId)
            ->firstOrFail();

        $this->assertSame($driver->id, $log->actor_id);
        $this->assertSame('POST', $log->request_method);
        $this->assertStringContainsString('/api/v1/driver/vehicles', $log->url);
        $this->assertSame('AUD 1001', $log->new_values['plate_number']);
        $this->assertArrayNotHasKey('file_path', $log->new_values);
    }

    public function test_updated_log_contains_only_changed_values_and_previous_values(): void
    {
        $driver = User::factory()->driver()->create();
        $profile = $driver->driverProfile()->create([
            'status' => 'available',
            'verification_status' => 'approved',
        ]);
        $vehicle = Vehicle::query()->create([
            'driver_profile_id' => $profile->id,
            'ownership_type' => VehicleOwnershipType::DRIVER,
            'name' => 'Old Jeep',
            'plate_number' => 'AUD 2001',
            'capacity' => 4,
            'status' => VehicleStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
        ]);
        AuditLog::query()->delete();

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/vehicles/{$vehicle->id}", [
            'capacity' => 5,
        ])->assertOk();

        $log = AuditLog::query()
            ->where('event', 'updated')
            ->where('subject_type', Vehicle::class)
            ->where('subject_id', $vehicle->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(4, $log->old_values['capacity']);
        $this->assertSame(5, $log->new_values['capacity']);
        $this->assertArrayNotHasKey('updated_at', $log->new_values);
        $this->assertSame(VerificationStatus::PENDING->value, $log->new_values['verification_status']);
        $this->assertSame(VehicleStatus::UNAVAILABLE->value, $log->new_values['status']);
    }

    public function test_admin_can_list_filter_and_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $actor = User::factory()->driver()->create();
        $log = AuditLog::query()->create([
            'actor_id' => $actor->id,
            'event' => 'updated',
            'subject_type' => Vehicle::class,
            'subject_id' => 99,
            'old_values' => ['capacity' => 4],
            'new_values' => ['capacity' => 5],
            'ip_address' => '127.0.0.1',
            'request_method' => 'PATCH',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/audit-logs?event=updated&subject_type='.urlencode(Vehicle::class).'&subject_id=99')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $log->id)
            ->assertJsonPath('data.data.0.actor.id', $actor->id);

        $this->getJson("/api/v1/admin/audit-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.old_values.capacity', 4)
            ->assertJsonPath('data.new_values.capacity', 5);
    }

    public function test_non_admin_cannot_access_audit_log_endpoints(): void
    {
        $driver = User::factory()->driver()->create();
        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/admin/audit-logs')->assertForbidden();
    }
}
