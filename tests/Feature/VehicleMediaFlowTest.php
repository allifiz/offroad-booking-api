<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleMediaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_driver_can_upload_and_replace_vehicle_document_with_file_cleanup(): void
    {
        [$driver, $vehicle] = $this->createDriverAndVehicle(approved: true);
        Sanctum::actingAs($driver);

        $first = $this->postJson("/api/v1/driver/vehicles/{$vehicle->id}/documents", [
            'type' => 'stnk',
            'document_number' => 'STNK-001',
            'expires_at' => now()->addYear()->toDateString(),
            'file' => UploadedFile::fake()->create('stnk.pdf', 100, 'application/pdf'),
        ]);

        $first->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'stnk')
            ->assertJsonPath('data.verification_status', VerificationStatus::PENDING->value);

        $document = VehicleDocument::query()->firstOrFail();
        $oldPath = $document->file_path;
        Storage::disk('public')->assertExists($oldPath);

        $vehicle->refresh();
        $this->assertSame(VerificationStatus::PENDING, $vehicle->verification_status);
        $this->assertSame(VehicleStatus::UNAVAILABLE, $vehicle->status);
        $this->assertNull($vehicle->verified_by);
        $this->assertNull($vehicle->verified_at);

        $replacement = $this->postJson("/api/v1/driver/vehicles/{$vehicle->id}/documents", [
            'type' => 'stnk',
            'document_number' => 'STNK-002',
            'expires_at' => now()->addYears(2)->toDateString(),
            'file' => UploadedFile::fake()->create('stnk-new.pdf', 120, 'application/pdf'),
        ]);

        $replacement->assertCreated()
            ->assertJsonPath('data.document_number', 'STNK-002');

        $document->refresh();
        $this->assertSame(1, VehicleDocument::query()->where('vehicle_id', $vehicle->id)->where('type', 'stnk')->count());
        $this->assertNotSame($oldPath, $document->file_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_driver_can_upload_reorder_and_delete_vehicle_photos(): void
    {
        [$driver, $vehicle] = $this->createDriverAndVehicle(approved: true);
        Sanctum::actingAs($driver);

        $frontResponse = $this->postJson("/api/v1/driver/vehicles/{$vehicle->id}/photos", [
            'type' => 'front',
            'sort_order' => 0,
            'photo' => UploadedFile::fake()->image('front.jpg'),
        ])->assertCreated();

        $leftResponse = $this->postJson("/api/v1/driver/vehicles/{$vehicle->id}/photos", [
            'type' => 'left',
            'sort_order' => 1,
            'photo' => UploadedFile::fake()->image('left.jpg'),
        ])->assertCreated();

        $frontId = $frontResponse->json('data.id');
        $leftId = $leftResponse->json('data.id');

        $front = VehiclePhoto::query()->findOrFail($frontId);
        $left = VehiclePhoto::query()->findOrFail($leftId);
        Storage::disk('public')->assertExists($front->file_path);
        Storage::disk('public')->assertExists($left->file_path);

        $vehicle->refresh();
        $this->assertSame(VerificationStatus::PENDING, $vehicle->verification_status);
        $this->assertSame(VehicleStatus::UNAVAILABLE, $vehicle->status);

        $this->putJson("/api/v1/driver/vehicles/{$vehicle->id}/photos/order", [
            'photos' => [
                ['id' => $left->id, 'sort_order' => 0],
                ['id' => $front->id, 'sort_order' => 1],
            ],
        ])->assertOk();

        $this->assertSame(0, $left->fresh()->sort_order);
        $this->assertSame(1, $front->fresh()->sort_order);

        $deletedPath = $left->file_path;
        $this->deleteJson("/api/v1/driver/vehicles/{$vehicle->id}/photos/{$left->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('vehicle_photos', ['id' => $left->id]);
        Storage::disk('public')->assertMissing($deletedPath);
    }

    public function test_invalid_photo_type_and_cross_driver_media_access_are_rejected(): void
    {
        [$owner, $vehicle] = $this->createDriverAndVehicle();
        [$otherDriver] = $this->createDriverAndVehicle();

        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/driver/vehicles/{$vehicle->id}/photos", [
            'type' => 'roof',
            'photo' => UploadedFile::fake()->image('roof.jpg'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('type');

        $photo = $vehicle->photos()->create([
            'type' => 'front',
            'file_path' => 'vehicles/photos/existing.jpg',
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put($photo->file_path, 'photo');

        Sanctum::actingAs($otherDriver);

        $this->postJson("/api/v1/driver/vehicles/{$vehicle->id}/documents", [
            'type' => 'stnk',
            'file' => UploadedFile::fake()->create('stnk.pdf', 100, 'application/pdf'),
        ])->assertNotFound();

        $this->putJson("/api/v1/driver/vehicles/{$vehicle->id}/photos/order", [
            'photos' => [
                ['id' => $photo->id, 'sort_order' => 0],
            ],
        ])->assertNotFound();

        $this->deleteJson("/api/v1/driver/vehicles/{$vehicle->id}/photos/{$photo->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('vehicle_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists($photo->file_path);
    }

    public function test_reorder_rejects_photo_from_another_vehicle(): void
    {
        [$driver, $vehicle] = $this->createDriverAndVehicle();
        [, $otherVehicle] = $this->createDriverAndVehicle();

        $ownedPhoto = $vehicle->photos()->create([
            'type' => 'front',
            'file_path' => 'vehicles/photos/owned.jpg',
            'sort_order' => 0,
        ]);
        $foreignPhoto = $otherVehicle->photos()->create([
            'type' => 'left',
            'file_path' => 'vehicles/photos/foreign.jpg',
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($driver);

        $this->putJson("/api/v1/driver/vehicles/{$vehicle->id}/photos/order", [
            'photos' => [
                ['id' => $ownedPhoto->id, 'sort_order' => 0],
                ['id' => $foreignPhoto->id, 'sort_order' => 1],
            ],
        ])->assertNotFound();

        $this->assertSame(0, $ownedPhoto->fresh()->sort_order);
        $this->assertSame(0, $foreignPhoto->fresh()->sort_order);
    }

    private function createDriverAndVehicle(bool $approved = false): array
    {
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => $approved ? DriverStatus::AVAILABLE : DriverStatus::UNAVAILABLE,
            'verification_status' => $approved ? VerificationStatus::APPROVED : VerificationStatus::PENDING,
            'available_points' => 0,
            'held_points' => 0,
        ]);

        $vehicle = Vehicle::query()->create([
            'driver_profile_id' => $profile->id,
            'ownership_type' => VehicleOwnershipType::DRIVER,
            'name' => 'Jeep Media Test '.fake()->unique()->numberBetween(1, 999999),
            'plate_number' => 'MED-'.fake()->unique()->numerify('######'),
            'brand' => 'Jeep',
            'model' => 'Wrangler',
            'year' => 2022,
            'capacity' => 4,
            'status' => $approved ? VehicleStatus::AVAILABLE : VehicleStatus::UNAVAILABLE,
            'verification_status' => $approved ? VerificationStatus::APPROVED : VerificationStatus::PENDING,
            'verified_by' => $approved ? User::factory()->admin()->create()->id : null,
            'verified_at' => $approved ? now() : null,
        ]);

        return [$driver, $vehicle];
    }
}
