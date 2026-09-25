<?php

namespace Database\Factories;

use App\Enums\ProgramStatus;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Program',
            'description' => fake()->sentence(),
            'district' => fake()->randomElement(['Dhaka', 'Chattogram', 'Rajshahi', 'Khulna']),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => ProgramStatus::Draft,
            'budget' => fake()->randomFloat(2, 50000, 500000),
            'target_beneficiaries' => fake()->numberBetween(50, 500),
        ];
    }
}
