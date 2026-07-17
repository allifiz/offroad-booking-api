<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_register_with_profile_documents_and_vehicle(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/v1/driver/register', [
            'name' => 'Driver Baru',
            'email' => 'driverbaru@example.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'identity_number' => 'ID-DRIVER-001',
            'license_number' => 'SIM-DRIVER-001',
            'address' => 'Yogyakarta',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
            'driver_documents' => [
                [
                    'type' => 'identity_card',
                    'file' => UploadedFile::fake()->image('ktp.jpg'),
                    'document_number' => 'ID-DRIVER-001',
                ],
                [
                    'type' => 'driver_license',
                    'file' => UploadedFile::fake()->image('sim.jpg'),
                    'document_number' => 'SIM-DRIVER-001',
                ],
            ],
            'vehicle' => [
                'name' => 'Jeep Driver Baru',
                'plate_number' => 'AB 9999 ZZ',
                'brand' => 'Toyota',
                'model' => 'Land Cruiser',
                'year' => 2020,
                'capacity' => 4,
            ],
            'vehicle_documents' => [
                [
                    'type' => 'registration_certificate',
                    'file' => UploadedFile::fake()->create('stnk.pdf', 100, 'application/pdf'),
                    'document_number' => 'STNK-001',
                ],
            ],
            'vehicle_photos' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('side.jpg'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', UserRole::DRIVER->value)
            ->assertJsonPath('data.driver_profile.availability_status', DriverStatus::UNAVAILABLE->value)
            ->assertJsonPath('data.driver_profile.verification_status', VerificationStatus::PENDING->value)
            ->assertJsonPath('data.driver_profile.vehicles.0.ownership_type', VehicleOwnershipType::DRIVER->value)
            ->assertJsonPath('data.driver_profile.vehicles.0.availability_status', VehicleStatus::UNAVAILABLE->value)
            ->assertJsonPath('data.driver_profile.vehicles.0.verification_status', VerificationStatus::PENDING->value)
            ->assertJsonPath('data.driver_profile.vehicles.0.documents_count', 1)
            ->assertJsonPath('data.driver_profile.vehicles.0.photos_count', 2);

        $this->assertDatabaseHas('users', ['email' => 'driverbaru@example.com', 'role' => UserRole::DRIVER->value]);
        $this->assertDatabaseHas('driver_documents', ['type' => 'identity_card']);
        $this->assertDatabaseHas('vehicles', ['plate_number' => 'AB 9999 ZZ']);
        $this->assertDatabaseCount('vehicle_photos', 2);
    }

    public function test_driver_registration_validates_required_files_and_unique_identity(): void
    {
        $this->postJson('/api/v1/driver/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'phone',
                'password',
                'identity_number',
                'license_number',
                'profile_photo',
                'driver_documents',
                'vehicle.name',
                'vehicle.plate_number',
                'vehicle.capacity',
                'vehicle_documents',
                'vehicle_photos',
            ]);
    }
}
