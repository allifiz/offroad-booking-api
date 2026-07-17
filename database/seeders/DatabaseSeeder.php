<?php

namespace Database\Seeders;

use App\Models\DriverProfile;
use App\Models\TourPackage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin Offroad',
            'email' => 'admin@offroad.test',
            'phone' => '081200000001',
            'password' => 'password',
        ]);

        User::factory()->customer()->create([
            'name' => 'Customer Demo',
            'email' => 'customer@offroad.test',
            'phone' => '081200000002',
            'password' => 'password',
        ]);

        User::factory()->customer()->count(9)->create();

        $drivers = User::factory()->driver()->count(5)->sequence(
            ['name' => 'Driver Andi', 'email' => 'driver1@offroad.test', 'phone' => '081300000001'],
            ['name' => 'Driver Budi', 'email' => 'driver2@offroad.test', 'phone' => '081300000002'],
            ['name' => 'Driver Candra', 'email' => 'driver3@offroad.test', 'phone' => '081300000003'],
            ['name' => 'Driver Dedi', 'email' => 'driver4@offroad.test', 'phone' => '081300000004'],
            ['name' => 'Driver Eko', 'email' => 'driver5@offroad.test', 'phone' => '081300000005'],
        )->create([
            'password' => 'password',
        ]);

        $drivers->each(function (User $driver): void {
            DriverProfile::factory()->for($driver)->create();
        });

        TourPackage::factory()->count(5)->create();
        Vehicle::factory()->count(6)->create();
    }
}
