<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    protected $model = DriverProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'status' => DriverStatus::AVAILABLE,
            'license_number' => fake()->unique()->bothify('SIM-########'),
            'identity_number' => fake()->unique()->numerify('################'),
            'address' => fake()->address(),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-21 years')->format('Y-m-d'),
            'joined_at' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'available_points' => fake()->numberBetween(0, 5000),
            'held_points' => 0,
        ];
    }
}
