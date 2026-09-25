<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'joining_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'status' => EmployeeStatus::Active,
        ];
    }
}
