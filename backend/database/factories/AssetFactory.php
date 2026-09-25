<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Vehicle', 'IT Equipment', 'Furniture', 'Office Equipment']),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'purchase_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'purchase_value' => fake()->randomFloat(2, 1000, 200000),
            'condition' => fake()->randomElement(['new', 'good', 'fair']),
            'status' => AssetStatus::Available,
        ];
    }
}
