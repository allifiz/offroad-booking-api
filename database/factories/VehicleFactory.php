<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'name' => 'Jeep '.fake()->unique()->bothify('##??'),
            'plate_number' => strtoupper(fake()->unique()->bothify('AB #### ??')),
            'brand' => fake()->randomElement(['Jeep', 'Toyota', 'Suzuki', 'Daihatsu']),
            'model' => fake()->randomElement(['Willys', 'Land Cruiser', 'Jimny', 'Taft']),
            'year' => fake()->numberBetween(1980, (int) date('Y')),
            'capacity' => fake()->numberBetween(3, 6),
            'status' => VehicleStatus::AVAILABLE,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
