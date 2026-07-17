<?php

namespace Database\Factories;

use App\Enums\TourPackageStatus;
use App\Models\TourPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TourPackage>
 */
class TourPackageFactory extends Factory
{
    protected $model = TourPackage::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Jalur Lava Merapi',
            'Sunrise Adventure',
            'Petualangan Kali Kuning',
            'Eksplorasi Bunker Kaliadem',
            'Merapi Long Trip',
            'Merapi Short Trip',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->paragraph(),
            'meeting_point' => fake()->address(),
            'duration_minutes' => fake()->randomElement([90, 120, 180, 240]),
            'minimum_participants' => 1,
            'maximum_participants' => fake()->numberBetween(4, 12),
            'price_per_person' => fake()->randomElement([150000, 200000, 250000, 300000]),
            'status' => TourPackageStatus::ACTIVE,
        ];
    }
}
